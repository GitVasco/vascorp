<?php

/**
 * GRE remitente manual (formato eFact "Guía de remisión remitente CSV 2.1").
 * Independiente de ControladorFacturacion::ctrGenerarGuia (flujo desde pedidos).
 * Estructura de filas copiada de ese generador, que EFACT ya procesa bien.
 */
class ControladorGreManual
{
    const RUC_REMITENTE = "20513613939";

    /** Motivos habilitados (Catálogo 20). 08/09 quedan fuera: exigen DAM, puerto, etc. */
    public static function motivos()
    {
        return array(
            "01" => "VENTA",
            "02" => "COMPRA",
            "04" => "TRASLADO ENTRE ESTABLECIMIENTOS DE LA MISMA EMPRESA",
            "13" => "OTROS",
            "14" => "VENTA SUJETA A CONFIRMACION DEL COMPRADOR",
        );
    }

    public static function remitente()
    {
        return array(
            "nombre" => "Corporacion Vasco S.A.C.",
            "tipo_doc" => "6",
            "doc" => self::RUC_REMITENTE,
            "ubigeo" => "150135",
            "direccion" => "CAL.SANTO TORIBIO NRO. 259",
            "urb" => "URB.SANTA LUISA 1RA ETAPA",
            "dpto" => "LIMA",
            "prov" => "LIMA",
            "dist" => "SAN MARTIN DE PORRES",
        );
    }

    public static function puedeUsar()
    {
        return isset($_SESSION["iniciarSesion"]) && $_SESSION["iniciarSesion"] === "ok"
            && ((isset($_SESSION["facturacion"]) && $_SESSION["facturacion"] == 1)
                || (isset($_SESSION["materiaprima"]) && $_SESSION["materiaprima"] == 1));
    }

    /**
     * Consulta RUC (tipo 6) o DNI (tipo 1) en la API que ya usa el sistema y devuelve
     * razón social/nombre, dirección y ubigeo listos para llenar un formulario.
     */
    public static function ctrConsultarDocumento($tipo, $numero)
    {
        $numero = preg_replace('/\D/', '', (string) $numero);
        if ($tipo === "6" && strlen($numero) !== 11) {
            return array("ok" => false, "msg" => "El RUC debe tener 11 dígitos.");
        }
        if ($tipo === "1" && strlen($numero) !== 8) {
            return array("ok" => false, "msg" => "El DNI debe tener 8 dígitos.");
        }
        if ($tipo !== "6" && $tipo !== "1") {
            return array("ok" => false, "msg" => "La búsqueda solo existe para RUC y DNI.");
        }

        require_once __DIR__ . "/../helpers/jsonpe.api.php";
        $r = $tipo === "6" ? JsonPeApi::consultarRuc($numero) : JsonPeApi::consultarDni($numero);
        if (empty($r["success"]) || empty($r["data"]) || !is_array($r["data"])) {
            return array("ok" => false, "msg" => isset($r["message"]) && $r["message"] !== "" ? (string) $r["message"] : "No se encontró el documento.");
        }
        $d = $r["data"];

        if ($tipo === "6") {
            $razon = isset($d["nombre_o_razon_social"]) ? $d["nombre_o_razon_social"] : "";
        } else {
            $razon = trim((isset($d["apellido_paterno"]) ? $d["apellido_paterno"] : "") . " "
                . (isset($d["apellido_materno"]) ? $d["apellido_materno"] : "") . " "
                . (isset($d["nombres"]) ? $d["nombres"] : ""));
        }

        $u = isset($d["ubigeo"]) ? $d["ubigeo"] : "";
        if (is_array($u)) {
            $u = count($u) ? end($u) : "";
        }
        $u = preg_match('/^\d{6}$/', (string) $u) ? (string) $u : "";
        $dpto = isset($d["departamento"]) ? $d["departamento"] : "";
        $prov = isset($d["provincia"]) ? $d["provincia"] : "";
        $dist = isset($d["distrito"]) ? $d["distrito"] : "";
        if ($u !== "") {
            $ub = ModeloGreManual::mdlUbigeoPorCodigo($u);
            if ($ub) {
                $dpto = $ub["departamento"];
                $prov = $ub["provincia"];
                $dist = $ub["distrito"];
            }
        }

        return array(
            "ok" => true,
            "razon_social" => self::t($razon, 100),
            "direccion" => self::t(isset($d["direccion"]) ? $d["direccion"] : "", 100),
            "ubigeo" => $u,
            "dpto" => self::t($dpto, 30),
            "prov" => self::t($prov, 30),
            "dist" => self::t($dist, 30),
        );
    }

    /**
     * Unidad de MP (TUND del sistema) -> código SUNAT de unidades_medidajf.
     * Heurística por nombre; lo que no se reconoce queda en C62 (piezas) y se puede cambiar en el formulario.
     */
    public static function ctrUnidadSunat($larga, $corta)
    {
        $t = strtoupper(trim($larga . " " . $corta));
        $t = strtr($t, array("Á" => "A", "É" => "E", "Í" => "I", "Ó" => "O", "Ú" => "U"));
        $reglas = array(
            "MTR" => '/METRO|\bMTS?\b|\bMT\b|\bM\b/',
            "KGM" => '/KILO|\bKGS?\b/',
            "DZN" => '/DOCENA|\bDOC\b/',
            "PR" => '/\bPAR(ES)?\b/',
            "BX" => '/CAJA/',
            "BG" => '/BOLSA/',
            "PAK" => '/PACK|PAQUETE/',
            "EST" => '/ESTUCHE/',
        );
        foreach ($reglas as $cod => $rx) {
            if (preg_match($rx, $t)) {
                return $cod;
            }
        }
        return "C62";
    }

    /** Mismos usuarios que ven "Cambiar Correlativo" en Guías Remisión. */
    public static function puedeCorregirCorrelativo()
    {
        return isset($_SESSION["id"]) && ($_SESSION["id"] == "70" || $_SESSION["id"] == "6");
    }

    /** Texto apto para CSV: sin comas ni saltos de línea. */
    private static function t($v, $max = 0)
    {
        $v = trim(preg_replace('/[\r\n\t,]+/', ' ', (string) $v));
        $v = preg_replace('/\s{2,}/', ' ', $v);
        if ($max > 0 && function_exists("mb_substr")) {
            $v = mb_substr($v, 0, $max, "UTF-8");
        }
        return $v;
    }

    private static function fechaValida($f)
    {
        $d = DateTime::createFromFormat("Y-m-d", (string) $f);
        return $d && $d->format("Y-m-d") === $f;
    }

    /**
     * Valida y normaliza lo que viene del formulario.
     * Devuelve array("ok"=>bool, "msg"=>string, "cab"=>array, "items"=>array).
     */
    public static function ctrNormalizar($in)
    {
        $err = array();
        $g = function ($k) use ($in) {
            return isset($in[$k]) ? self::t($in[$k]) : "";
        };
        $motivos = self::motivos();
        $rem = self::remitente();

        $c = array();
        $c["fecha_emision"] = $g("fecha_emision");
        $c["fecha_traslado"] = $g("fecha_traslado");
        if (!self::fechaValida($c["fecha_emision"]) || !self::fechaValida($c["fecha_traslado"])) {
            $err[] = "Fechas inválidas.";
        } elseif ($c["fecha_traslado"] < $c["fecha_emision"]) {
            $err[] = "La fecha de traslado no puede ser anterior a la emisión.";
        }

        $c["motivo_cod"] = $g("motivo_cod");
        if (!isset($motivos[$c["motivo_cod"]])) {
            $err[] = "Motivo de traslado no habilitado.";
        }
        $c["motivo_desc"] = self::t(isset($in["motivo_desc"]) ? $in["motivo_desc"] : "", 100);
        if ($c["motivo_desc"] === "" && isset($motivos[$c["motivo_cod"]])) {
            $c["motivo_desc"] = $motivos[$c["motivo_cod"]];
        }

        $c["modalidad"] = $g("modalidad");
        if ($c["modalidad"] !== "01" && $c["modalidad"] !== "02") {
            $err[] = "Modalidad de traslado inválida.";
        }

        $c["peso_kg"] = round((float) str_replace(",", ".", $g("peso_kg")), 3);
        if ($c["peso_kg"] <= 0) {
            $err[] = "El peso bruto total debe ser mayor a 0.";
        }
        $b = $g("bultos");
        $c["bultos"] = ($b !== "" && ctype_digit($b)) ? (int) $b : null;
        if ($c["bultos"] === null || $c["bultos"] < 1) {
            $err[] = "El número de bultos es obligatorio.";
        }
        $c["observaciones"] = self::t(isset($in["observaciones"]) ? $in["observaciones"] : "", 250);

        // Destinatario (motivo 04: la misma empresa)
        $c["dest_origen"] = in_array($g("dest_origen"), array("CLIENTE", "PROVEEDOR", "TALLER", "EMPRESA", "MANUAL"), true) ? $g("dest_origen") : "MANUAL";
        $c["dest_codigo"] = self::t($g("dest_codigo"), 20);
        if ($c["motivo_cod"] === "04") {
            $c["dest_origen"] = "EMPRESA";
            $c["dest_codigo"] = "";
            $c["dest_nombre"] = $rem["nombre"];
            $c["dest_tipo_doc"] = $rem["tipo_doc"];
            $c["dest_doc"] = $rem["doc"];
        } else {
            $c["dest_nombre"] = self::t($g("dest_nombre"), 100);
            $c["dest_tipo_doc"] = $g("dest_tipo_doc");
            $c["dest_doc"] = strtoupper($g("dest_doc"));
        }
        $c["dest_email"] = self::t($g("dest_email"), 100);
        $sv = $g("servicio");
        $c["servicio"] = ($sv !== "" && preg_match('/^[A-Za-z0-9_-]{1,20}$/', $sv)) ? $sv : null;
        if ($c["dest_nombre"] === "") {
            $err[] = "Falta el nombre del destinatario.";
        }
        $td = $c["dest_tipo_doc"];
        $dd = $c["dest_doc"];
        if (!in_array($td, array("0", "1", "4", "6", "7", "A"), true)) {
            $err[] = "Tipo de documento del destinatario inválido.";
        } elseif (($td === "1" && !preg_match('/^\d{8}$/', $dd))
            || ($td === "6" && !preg_match('/^\d{11}$/', $dd))
            || (($td === "4" || $td === "7") && !preg_match('/^[A-Z0-9]{1,12}$/', $dd))
            || (($td === "0" || $td === "A") && !preg_match('/^[A-Z0-9]{1,15}$/', $dd))
        ) {
            $err[] = "Número de documento del destinatario no coincide con su tipo.";
        }

        // Puntos de partida y llegada
        foreach (array("par" => "partida", "lle" => "llegada") as $p => $nom) {
            $c[$p . "_ubigeo"] = $g($p . "_ubigeo");
            $c[$p . "_direccion"] = self::t(isset($in[$p . "_direccion"]) ? $in[$p . "_direccion"] : "", 100);
            $c[$p . "_dpto"] = self::t(isset($in[$p . "_dpto"]) ? $in[$p . "_dpto"] : "", 30);
            $c[$p . "_prov"] = self::t(isset($in[$p . "_prov"]) ? $in[$p . "_prov"] : "", 30);
            $c[$p . "_dist"] = self::t(isset($in[$p . "_dist"]) ? $in[$p . "_dist"] : "", 30);
            if (!preg_match('/^\d{6}$/', $c[$p . "_ubigeo"])) {
                $err[] = "Ubigeo de $nom inválido (6 dígitos).";
            }
            if ($c[$p . "_direccion"] === "" || $c[$p . "_dpto"] === "" || $c[$p . "_prov"] === "" || $c[$p . "_dist"] === "") {
                $err[] = "Completa dirección, departamento, provincia y distrito de $nom.";
            }
        }

        // Transporte
        foreach (array("transp_ruc", "transp_nombre", "transp_mtc", "chofer_tipo_doc", "chofer_doc", "chofer_nombres", "chofer_apellidos", "chofer_licencia", "placa") as $k) {
            $c[$k] = "";
        }
        if ($c["modalidad"] === "01") {
            $c["transp_ruc"] = $g("transp_ruc");
            $c["transp_nombre"] = self::t(isset($in["transp_nombre"]) ? $in["transp_nombre"] : "", 100);
            $c["transp_mtc"] = $g("transp_mtc");
            if (!preg_match('/^\d{11}$/', $c["transp_ruc"]) || $c["transp_nombre"] === "") {
                $err[] = "Transporte público: RUC (11 dígitos) y razón social del transportista son obligatorios.";
            }
        } elseif ($c["modalidad"] === "02") {
            $c["chofer_tipo_doc"] = $g("chofer_tipo_doc") !== "" ? $g("chofer_tipo_doc") : "1";
            $c["chofer_doc"] = strtoupper($g("chofer_doc"));
            $c["chofer_nombres"] = self::t(isset($in["chofer_nombres"]) ? $in["chofer_nombres"] : "", 50);
            $c["chofer_apellidos"] = self::t(isset($in["chofer_apellidos"]) ? $in["chofer_apellidos"] : "", 50);
            $c["chofer_licencia"] = $g("chofer_licencia");
            $c["placa"] = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $g("placa")));
            if ($c["chofer_doc"] === "" || $c["chofer_nombres"] === "" || $c["chofer_apellidos"] === "" || $c["placa"] === "") {
                $err[] = "Transporte privado: conductor (documento, nombres, apellidos) y placa son obligatorios.";
            }
            if ($c["chofer_tipo_doc"] === "1" && !preg_match('/^\d{8}$/', $c["chofer_doc"])) {
                $err[] = "El DNI del conductor debe tener 8 dígitos.";
            }
            if (strlen($c["placa"]) > 8) {
                $err[] = "La placa no puede superar 8 caracteres.";
            }
        }

        // Documentos relacionados
        $docs = array();
        $raw = isset($in["docs_rel"]) ? $in["docs_rel"] : array();
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (is_array($raw)) {
            foreach ($raw as $d) {
                $num = self::t(isset($d["numero"]) ? $d["numero"] : "", 20);
                $tip = isset($d["tipo"]) ? self::t($d["tipo"]) : "06";
                if ($num === "") {
                    continue;
                }
                if (!in_array($tip, array("01", "02", "03", "04", "05", "06"), true)) {
                    $err[] = "Tipo de documento relacionado inválido.";
                    continue;
                }
                $docs[] = array("tipo" => $tip, "numero" => $num);
            }
        }
        $c["docs_rel"] = count($docs) ? json_encode($docs) : null;

        // Ítems
        $items = array();
        $rawItems = isset($in["items"]) ? $in["items"] : array();
        if (is_string($rawItems)) {
            $rawItems = json_decode($rawItems, true);
        }
        if (is_array($rawItems)) {
            foreach ($rawItems as $it) {
                $desc = self::t(isset($it["descripcion"]) ? $it["descripcion"] : "", 250);
                $cant = round((float) str_replace(",", ".", isset($it["cantidad"]) ? $it["cantidad"] : "0"), 3);
                $uni = strtoupper(self::t(isset($it["unidad_cod"]) ? $it["unidad_cod"] : "C62", 3));
                if ($desc === "" || $cant <= 0) {
                    $err[] = "Cada ítem necesita descripción y cantidad mayor a 0.";
                    break;
                }
                $origen = isset($it["origen"]) && in_array($it["origen"], array("MODELO", "ARTICULO", "MP", "MANUAL"), true) ? $it["origen"] : "MANUAL";
                $nota = isset($it["nota"]) ? self::t($it["nota"], 20) : "";
                $items[] = array(
                    "origen" => $origen,
                    "nota" => preg_match('/^[A-Za-z0-9-]{1,20}$/', $nota) ? $nota : "",
                    "codigo" => self::t(isset($it["codigo"]) ? $it["codigo"] : "", 16),
                    "descripcion" => $desc,
                    "unidad_cod" => $uni !== "" ? $uni : "C62",
                    "unidad_desc" => self::t(isset($it["unidad_desc"]) ? $it["unidad_desc"] : "", 15),
                    "cantidad" => $cant,
                );
            }
        }
        if (!count($items)) {
            $err[] = "Agrega al menos un ítem.";
        }

        if (count($err)) {
            return array("ok" => false, "msg" => implode(" ", array_unique($err)));
        }
        return array("ok" => true, "cab" => $c, "items" => $items);
    }

    public static function ctrGuardar($id, $serie, $in)
    {
        $n = self::ctrNormalizar($in);
        if (!$n["ok"]) {
            return $n;
        }
        $usuario = isset($_SESSION["nombre"]) ? $_SESSION["nombre"] : "";
        $r = ModeloGreManual::mdlGuardar((int) $id, $serie, $n["cab"], $n["items"], $usuario);
        $c = $n["cab"];
        // Recordar los datos del taller para las próximas guías
        if (!empty($r["ok"]) && isset($in["guardar_taller"]) && $in["guardar_taller"] === "1"
            && $c["dest_origen"] === "TALLER" && $c["dest_codigo"] !== "") {
            ModeloGreManual::mdlGuardarDatosTaller($c["dest_codigo"], array(
                "razon_social" => $c["dest_nombre"], "tipo_doc" => $c["dest_tipo_doc"], "doc" => $c["dest_doc"],
                "email" => $c["dest_email"], "direccion" => $c["lle_direccion"], "ubigeo" => $c["lle_ubigeo"],
                "dpto" => $c["lle_dpto"], "prov" => $c["lle_prov"], "dist" => $c["lle_dist"],
            ), $usuario);
        }
        return $r;
    }

    /** config.php usa rutas relativas a /ajax (dev) o absolutas (prod). */
    private static function rutaFisica($ruta)
    {
        if (preg_match('#^([A-Za-z]:|/|\\\\)#', $ruta)) {
            return $ruta;
        }
        return __DIR__ . "/" . $ruta;
    }

    /**
     * Arma el CSV, lo deja en la carpeta que vigila EFACT y marca ENVIADO.
     */
    public static function ctrEnviar($id)
    {
        $g = ModeloGreManual::mdlObtener((int) $id);
        if (!$g) {
            return array("ok" => false, "msg" => "Guía no encontrada.");
        }
        if ($g["tipo"] === "INTERNA") {
            return array("ok" => false, "msg" => "Una guía interna no se envía a EFACT. Conviértela a electrónica primero.");
        }
        if ($g["estado"] !== "GENERADO") {
            return array("ok" => false, "msg" => "La guía ya está " . $g["estado"] . ".");
        }
        // Revalidar con lo guardado (por si se editó directo en BD)
        $chk = self::ctrNormalizar(array_merge($g, array("items" => $g["items"], "docs_rel" => $g["docs_rel"])));
        if (!$chk["ok"]) {
            return array("ok" => false, "msg" => $chk["msg"]);
        }
        // Usar los valores ya limpios (sin comas ni saltos de línea)
        $g = array_merge($g, $chk["cab"]);
        $g["items"] = $chk["items"];

        $r = self::remitente();
        $docs = $g["docs_rel"] ? json_decode($g["docs_rel"], true) : array();
        $privado = $g["modalidad"] === "02";
        $nItems = count($g["items"]);
        $nDocs = count($docs);
        $hora = date("H:i:s");

        $f1 = implode(",", array($g["fecha_emision"], $g["documento"], "09", $nItems, "", $nDocs > 0 ? $nDocs : "1",
            $privado ? "1" : "", $privado ? "1" : "", "", $hora)) . ",";
        $f2 = ",,,,,,,,,,,,,,";
        $f3 = array();
        foreach ($docs as $d) {
            $f3[] = $d["numero"] . "," . $d["tipo"] . ",,,ATTACH_DOC,";
        }
        if (!$nDocs) {
            $f3[] = ",,,,,";
        }
        $f4 = $privado
            ? implode(",", array($g["chofer_doc"], $g["chofer_tipo_doc"], $g["chofer_nombres"], $g["chofer_apellidos"], $g["chofer_licencia"], "ATTACH_DOC")) . ","
            : ",,,,,,";
        $f5 = $privado ? $g["placa"] . ",,,,ATTACH_DOC," : ",,,,,";
        $f6 = ",,,,";
        $f7 = implode(",", array($r["nombre"], $r["tipo_doc"], $r["doc"], $r["ubigeo"], $r["direccion"], $r["urb"],
            $r["dpto"], $r["prov"], $r["dist"], "PE")) . ",";
        // Destinatario: dirección y ubigeo de llegada (lo que EFACT toma del envío)
        $f8 = implode(",", array($g["dest_nombre"], $g["dest_tipo_doc"], $g["dest_doc"], $g["lle_ubigeo"], $g["lle_direccion"], "-",
            $g["lle_dpto"], $g["lle_prov"], $g["lle_dist"], "PE", $g["dest_email"])) . ",";
        $f9 = ",,,,,,,,,,,,,,,,,,,,";
        $f10 = implode(",", array($g["motivo_cod"], $g["motivo_desc"], "", number_format((float) $g["peso_kg"], 3, ".", ""), "KGM",
            $g["modalidad"], $g["fecha_traslado"],
            $privado ? "" : $g["transp_nombre"], $privado ? "" : "6", $privado ? "" : $g["transp_ruc"],
            $g["par_ubigeo"], $g["par_direccion"], "-", $g["par_dpto"], $g["par_prov"], $g["par_dist"],
            $g["lle_ubigeo"], $g["lle_direccion"], "-", $g["lle_dpto"], $g["lle_prov"], $g["lle_dist"],
            $g["bultos"] !== null ? $g["bultos"] : ""))
            . ",,,," . ($privado ? "" : $g["transp_mtc"]) . str_repeat(",", 13) . ",";
        $f11 = $g["observaciones"] . ",,,,";

        $lineas = array($f1, $f2);
        foreach ($f3 as $x) {
            $lineas[] = $x;
        }
        $lineas = array_merge($lineas, array($f4, $f5, $f6, $f7, $f8, $f9, $f10, $f11));
        $i = 0;
        foreach ($g["items"] as $it) {
            $i++;
            $desc = str_replace(array("Ñ", "ñ"), array("N", "n"), $it["descripcion"]);
            $lineas[] = implode(",", array($i, $it["unidad_cod"], $it["unidad_desc"], rtrim(rtrim(number_format((float) $it["cantidad"], 3, ".", ""), "0"), "."),
                $desc, $it["codigo"])) . ",";
        }
        $lineas[] = "FF00FF";

        require_once __DIR__ . "/config.php";
        $nombre = self::RUC_REMITENTE . "-09-" . $g["documento"];
        $origenDir = self::rutaFisica(ORIGEN);
        $destinoDir = self::rutaFisica(DESTINO_GUIA_REMISION);
        if (!is_dir($origenDir)) {
            @mkdir($origenDir, 0775, true);
        }
        $tmp = $origenDir . $nombre . ".txt";
        if (file_put_contents($tmp, implode(PHP_EOL, $lineas)) === false) {
            return array("ok" => false, "msg" => "No se pudo escribir el archivo temporal.");
        }
        if (!@rename($tmp, $destinoDir . $nombre . ".csv")) {
            @unlink($tmp);
            return array("ok" => false, "msg" => "No se pudo dejar el CSV en la carpeta de EFACT.");
        }
        ModeloGreManual::mdlMarcarEnviado((int) $id, $nombre . ".csv");
        return array("ok" => true, "documento" => $g["documento"], "archivo" => $nombre . ".csv");
    }
}
