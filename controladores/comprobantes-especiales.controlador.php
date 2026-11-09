<?php

/**
 * Comprobantes especiales: boleta/factura electrónica fuera del flujo de ventas.
 * Tipo implementado: GRATUITA (operaciones gratuitas / obsequios).
 *
 * REGLA: el CSV usa la MISMA estructura que ControladorFacturacion::ctrGenerarFEFacBolA
 * (la que eFact ya procesa): 9 filas + FF00FF, fechas dd/mm/yyyy, ítems desde la fila 8.
 * Solo cambian los campos propios de una operación gratuita
 * (ver docs/efact/COMPROBANTES-ESPECIALES-diseno.md §2c y §6).
 * No modifica ni reutiliza el flujo de ventas.
 */
class ControladorComprobantesEspeciales
{
    const RUC_EMISOR = "20513613939";
    const IGV = 0.18;
    const MAX_RECEPTORES = 300;

    const TIPO_DEFECTO = "GRATUITA";

    /**
     * Registro de tipos de comprobante especial. Hoy solo GRATUITA; para agregar otro:
     * un perfil aquí + un método lineas<Tipo>() abajo (ver docs/efact/COMPROBANTES-ESPECIALES-diseno.md §11).
     * Cada perfil declara lo propio del tipo; la estructura base del CSV es común.
     */
    public static function perfiles()
    {
        return array(
            "GRATUITA" => array(
                "nombre" => "Operación gratuita (obsequios)",
                "corto" => "Gratuita",
                "categorias" => array("TRABAJADOR" => "Trabajador", "CLIENTE" => "Cliente", "PROVEEDOR" => "Proveedor", "OTRO" => "Otro"),
                "afectaciones" => self::afectacionesGratuita(),
                "ayuda" => array(
                    "titulo" => "Cómo emitir una operación gratuita",
                    "pasos" => array(
                        "Un comprobante por receptor: DNI (8 dígitos) = boleta; RUC (11) = factura.",
                        "El importe total sale en S/ 0.00; el valor del regalo va como operación gratuita.",
                        "Valor referencial: por unidad y sin IGV (costo de compra u otro que defina Contabilidad).",
                        "Con IGV (11–16): el IGV 18 % se muestra solo como informativo; lo asume Vasco.",
                        "Motivo: texto claro, ej. ENTREGA A TRABAJADORES.",
                        "Fecha de emisión: hasta 7 días atrás.",
                    ),
                    "codigos_titulo" => "Qué afectación elegir",
                    "codigos" => array(
                        array("Premio de sorteo o concurso", "11"),
                        array("Donación a una entidad", "12"),
                        array("Obsequio a clientes / proveedores", "13"),
                        array("Con logo o fin publicitario", "14"),
                        array("Canasta o regalo a trabajadores", "16"),
                    ),
                    "nota" => "«Sin IGV» (inafecto) está desactivado hasta que Contabilidad lo confirme. Si es una prueba: enviar, revisar en eFact, dar de baja en su portal y pulsar «Marcar baja» en el listado.",
                ),
                "sugerido" => array(
                    "TRABAJADOR" => array("afe" => "16", "motivo" => "ENTREGA A TRABAJADORES"),
                    "CLIENTE" => array("afe" => "13", "motivo" => "OBSEQUIO A CLIENTES"),
                    "PROVEEDOR" => array("afe" => "13", "motivo" => "OBSEQUIO A PROVEEDORES"),
                    "OTRO" => array("afe" => "13", "motivo" => ""),
                ),
            ),
        );
    }

    public static function perfil($tipo)
    {
        $p = self::perfiles();
        return isset($p[$tipo]) ? $p[$tipo] : null;
    }

    /** id => nombre de los tipos disponibles. */
    public static function tipos()
    {
        $t = array();
        foreach (self::perfiles() as $k => $p) {
            $t[$k] = $p["nombre"];
        }
        return $t;
    }

    public static function afectaciones($tipo = self::TIPO_DEFECTO)
    {
        $p = self::perfil($tipo);
        return $p ? $p["afectaciones"] : array();
    }

    public static function categorias($tipo = self::TIPO_DEFECTO)
    {
        $p = self::perfil($tipo);
        return $p ? $p["categorias"] : array();
    }

    /** Nombres oficiales del Catálogo 07 de SUNAT (para mostrar en impresión y listado). */
    public static function catalogoAfectaciones()
    {
        return array(
            "11" => "Gravado – Retiro por premio",
            "12" => "Gravado – Retiro por donación",
            "13" => "Gravado – Retiro",
            "14" => "Gravado – Retiro por publicidad",
            "15" => "Gravado – Bonificaciones",
            "16" => "Gravado – Retiro por entrega a trabajadores",
            "21" => "Exonerado – Transferencia gratuita",
            "31" => "Inafecto – Retiro por bonificación",
            "32" => "Inafecto – Retiro",
            "33" => "Inafecto – Retiro por muestras médicas",
            "34" => "Inafecto – Retiro por convenio colectivo",
            "35" => "Inafecto – Retiro por premio",
            "36" => "Inafecto – Retiro por publicidad",
        );
    }

    /** "16 · Gravado – Retiro por entrega a trabajadores" (o solo el código si no está en el catálogo). */
    public static function textoAfectacion($codigo)
    {
        $c = self::catalogoAfectaciones();
        return isset($c[$codigo]) ? $codigo . " · " . $c[$codigo] : (string) $codigo;
    }

    /** Afectaciones del tipo GRATUITA (Catálogo 07). 11-16 = gravado retiro (IGV informativo). */
    private static function afectacionesGratuita()
    {
        $a = array(
            "11" => "11 · Gravado – Retiro por premio",
            "12" => "12 · Gravado – Retiro por donación",
            "13" => "13 · Gravado – Retiro (obsequio general)",
            "14" => "14 · Gravado – Retiro por publicidad",
            "16" => "16 · Gravado – Retiro por entrega a trabajadores",
        );
        if (self::permiteInafecto()) {
            $a["32"] = "32 · Inafecto – Retiro (sin IGV)";
            $a["34"] = "34 · Inafecto – Retiro por convenio colectivo (sin IGV)";
        }
        return $a;
    }

    /** Inafecto (sin IGV) desactivado hasta que Contabilidad confirme el sustento. */
    public static function permiteInafecto()
    {
        return defined("GRATUITAS_PERMITE_INAFECTO") && GRATUITAS_PERMITE_INAFECTO === true;
    }

    public static function unidades()
    {
        return array("C62" => "Pieza (C62)", "NIU" => "Unidad (NIU)", "ZZ" => "Servicio (ZZ)", "KGM" => "Kilogramo (KGM)", "BX" => "Caja (BX)");
    }

    /**
     * Solo los usuarios listados (por id de sesión). Se puede definir
     * COMPROBANTES_ESPECIALES_USUARIOS en config.php, p. ej. array("70"). Por defecto los mismos
     * usuarios que corrigen correlativos en Guías manuales.
     */
    public static function puedeUsar()
    {
        if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok" || !isset($_SESSION["id"])) {
            return false;
        }
        $permitidos = defined("COMPROBANTES_ESPECIALES_USUARIOS") ? COMPROBANTES_ESPECIALES_USUARIOS : array("70", "6");
        return in_array((string) $_SESSION["id"], array_map("strval", $permitidos), true);
    }

    private static function t($v, $max = 0)
    {
        $v = trim(preg_replace('/[\r\n\t,]+/', ' ', (string) $v));
        $v = preg_replace('/\s{2,}/', ' ', $v);
        if ($max > 0 && function_exists("mb_substr")) {
            $v = mb_substr($v, 0, $max, "UTF-8");
        }
        return $v;
    }

    private static function esGravado($afectacion)
    {
        return (int) $afectacion >= 11 && (int) $afectacion <= 16;
    }

    private static function fechaValida($f)
    {
        $d = DateTime::createFromFormat("Y-m-d", (string) $f);
        return $d && $d->format("Y-m-d") === $f;
    }

    /**
     * Valida el formulario y calcula importes (la parte de ítems y montos es la del tipo GRATUITA:
     * valor referencial, IGV informativo). Otro tipo con otra lógica: separar aquí por perfil. Devuelve array(ok, msg) o array(ok, docs).
     * Entrada: fecha, categoria, afectacion, motivo, observaciones,
     *          items[] (descripcion, codigo, unidad, cantidad, valor_ref_unit),
     *          receptores[] (doc, nombre, direccion, email, ubigeo, dpto, prov, dist).
     */
    public static function ctrNormalizarLote($in)
    {
        $g = function ($k, $max = 0) use ($in) {
            return self::t(isset($in[$k]) ? $in[$k] : "", $max);
        };
        $fecha = $g("fecha");
        if (!self::fechaValida($fecha)) {
            return array("ok" => false, "msg" => "Fecha de emisión inválida.");
        }
        $hoy = date("Y-m-d");
        if ($fecha > $hoy) {
            return array("ok" => false, "msg" => "La fecha de emisión no puede ser futura.");
        }
        if ($fecha < date("Y-m-d", strtotime("-7 days"))) {
            return array("ok" => false, "msg" => "La fecha de emisión no puede tener más de 7 días de antigüedad.");
        }

        $tipoOp = $g("tipo_operacion") !== "" ? $g("tipo_operacion") : self::TIPO_DEFECTO;
        $perfil = self::perfil($tipoOp);
        if (!$perfil) {
            return array("ok" => false, "msg" => "Tipo de comprobante no disponible.");
        }
        $cat = $g("categoria");
        if (!isset($perfil["categorias"][$cat])) {
            return array("ok" => false, "msg" => "Elige la categoría del destinatario.");
        }
        $afe = $g("afectacion");
        if (!isset($perfil["afectaciones"][$afe])) {
            return array("ok" => false, "msg" => "Afectación no habilitada.");
        }
        $motivo = $g("motivo", 150);
        if ($motivo === "") {
            return array("ok" => false, "msg" => "Escribe el motivo (ej. ENTREGA A TRABAJADORES).");
        }
        $obs = $g("observaciones", 200);

        // Ítems
        $items = array();
        $unids = self::unidades();
        $rawItems = isset($in["items"]) && is_array($in["items"]) ? $in["items"] : array();
        foreach ($rawItems as $it) {
            $desc = self::t(isset($it["descripcion"]) ? $it["descripcion"] : "", 250);
            $desc = str_replace(array("Ñ", "ñ"), array("N", "n"), $desc);
            if ($desc === "") {
                continue;
            }
            $unidad = isset($it["unidad"]) ? self::t($it["unidad"]) : "C62";
            if (!isset($unids[$unidad])) {
                return array("ok" => false, "msg" => "Unidad inválida en el ítem «" . $desc . "».");
            }
            $cant = isset($it["cantidad"]) ? (float) str_replace(",", ".", $it["cantidad"]) : 0;
            $ref = isset($it["valor_ref_unit"]) ? (float) str_replace(",", ".", $it["valor_ref_unit"]) : 0;
            if ($cant <= 0 || $cant > 999999) {
                return array("ok" => false, "msg" => "Cantidad inválida en el ítem «" . $desc . "».");
            }
            if ($ref <= 0 || $ref > 99999999) {
                return array("ok" => false, "msg" => "El valor referencial debe ser mayor a 0 en «" . $desc . "».");
            }
            $ref = round($ref, 2);
            $vv = round($ref * $cant, 2);
            $items[] = array(
                "codigo" => self::t(isset($it["codigo"]) ? $it["codigo"] : "", 30),
                "descripcion" => $desc,
                "unidad_cod" => $unidad,
                "cantidad" => round($cant, 3),
                "valor_ref_unit" => $ref,
                "valor_venta" => $vv,
                "igv_informativo" => self::esGravado($afe) ? round($vv * self::IGV, 2) : 0.00,
            );
        }
        if (!count($items)) {
            return array("ok" => false, "msg" => "Agrega al menos un ítem.");
        }
        if (count($items) > 100) {
            return array("ok" => false, "msg" => "Máximo 100 ítems por comprobante.");
        }
        $total = 0;
        $igv = 0;
        foreach ($items as $it) {
            $total += $it["valor_venta"];
            $igv += $it["igv_informativo"];
        }
        $total = round($total, 2);
        $igv = round($igv, 2);

        // Receptores: uno por comprobante
        $rec = isset($in["receptores"]) && is_array($in["receptores"]) ? $in["receptores"] : array();
        if (!count($rec)) {
            return array("ok" => false, "msg" => "Agrega al menos un receptor.");
        }
        if (count($rec) > self::MAX_RECEPTORES) {
            return array("ok" => false, "msg" => "Máximo " . self::MAX_RECEPTORES . " receptores por lote.");
        }
        $lote = "L" . date("ymd-His");
        $vistos = array();
        $docs = array();
        $n = 0;
        foreach ($rec as $r) {
            $n++;
            $doc = preg_replace('/\D/', '', isset($r["doc"]) ? (string) $r["doc"] : "");
            if (strlen($doc) === 8) {
                $tipoRec = "1";
                $tipoDoc = "03";
            } elseif (strlen($doc) === 11 && preg_match('/^(10|15|17|20)/', $doc)) {
                $tipoRec = "6";
                $tipoDoc = "01";
            } else {
                return array("ok" => false, "msg" => "Receptor " . $n . ": documento inválido (DNI de 8 o RUC de 11 dígitos).");
            }
            if (isset($vistos[$doc])) {
                return array("ok" => false, "msg" => "Receptor " . $n . ": el documento " . $doc . " está repetido en el lote.");
            }
            $vistos[$doc] = true;
            $nombre = self::t(isset($r["nombre"]) ? $r["nombre"] : "", 100);
            if ($nombre === "") {
                return array("ok" => false, "msg" => "Receptor " . $n . " (" . $doc . "): falta el nombre o razón social.");
            }
            $ub = preg_replace('/\D/', '', isset($r["ubigeo"]) ? (string) $r["ubigeo"] : "");
            $docs[] = array(
                "cab" => array(
                    "tipo_doc" => $tipoDoc,
                    "tipo_operacion" => $tipoOp,
                    "fecha_emision" => $fecha,
                    "moneda" => "PEN",
                    "lote" => $lote,
                    "categoria" => $cat,
                    "afectacion" => $afe,
                    "motivo" => $motivo,
                    "observaciones" => $obs !== "" ? $obs : null,
                    "receptor_tipo_doc" => $tipoRec,
                    "receptor_doc" => $doc,
                    "receptor_nombre" => $nombre,
                    "receptor_direccion" => self::t(isset($r["direccion"]) ? $r["direccion"] : "", 100) ?: "-",
                    "receptor_ubigeo" => strlen($ub) === 6 ? $ub : null,
                    "receptor_dpto" => self::t(isset($r["dpto"]) ? $r["dpto"] : "", 30) ?: null,
                    "receptor_prov" => self::t(isset($r["prov"]) ? $r["prov"] : "", 30) ?: null,
                    "receptor_dist" => self::t(isset($r["dist"]) ? $r["dist"] : "", 30) ?: null,
                    "receptor_email" => self::t(isset($r["email"]) ? $r["email"] : "", 100) ?: null,
                    "total_gratuito" => $total,
                    "igv_informativo" => $igv,
                    "aprobado_por" => null, // el responsable es el usuario de sesión (usuario_registro)
                ),
                "items" => $items,
            );
        }
        return array("ok" => true, "docs" => $docs, "lote" => $lote);
    }

    public static function ctrCrearLote($in)
    {
        $n = self::ctrNormalizarLote($in);
        if (!$n["ok"]) {
            return $n;
        }
        $usuario = isset($_SESSION["nombre"]) ? $_SESSION["nombre"] : "";
        $r = ModeloComprobantesEspeciales::mdlCrearLote($n["docs"], $usuario);
        if (!empty($r["ok"])) {
            $r["lote"] = $n["lote"];
        }
        return $r;
    }

    /* =====================================================
       CSV
    ===================================================== */

    /** Índice (0-based) de una letra de columna: A=0, Z=25, AA=26... */
    private static function col($letras)
    {
        $n = 0;
        foreach (str_split($letras) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }
        return $n - 1;
    }

    /** Fila vacía de $n columnas con los valores {LETRA => valor} puestos. */
    private static function fila($n, $valores)
    {
        $f = array_fill(0, $n, "");
        foreach ($valores as $letra => $v) {
            $f[self::col($letra)] = (string) $v;
        }
        return implode(",", $f);
    }

    private static function m($v)
    {
        return number_format((float) $v, 2, ".", "");
    }

    /**
     * Arma las líneas del CSV del comprobante (array de mdlObtener). Función pura:
     * no escribe nada ni toca la BD. Devuelve array(lineas, nombre).
     */
    public static function armarLineas($g)
    {
        $tipo = isset($g["tipo_operacion"]) && $g["tipo_operacion"] !== "" ? $g["tipo_operacion"] : self::TIPO_DEFECTO;
        $metodo = "lineas" . ucfirst(strtolower($tipo));
        if (!self::perfil($tipo) || !method_exists(__CLASS__, $metodo)) {
            throw new RuntimeException("Tipo de comprobante sin generador de CSV: " . $tipo);
        }
        return self::$metodo($g);
    }

    /** CSV del tipo GRATUITA: estructura de venta normal; cambian solo los campos de operación gratuita. */
    private static function lineasGratuita($g)
    {
        $esFactura = $g["tipo_doc"] === "01";
        $gravado = self::esGravado($g["afectacion"]);
        $total = (float) $g["total_gratuito"];
        $igv = $gravado ? (float) $g["igv_informativo"] : 0;
        $n = count($g["items"]);
        $unidades = 0;
        foreach ($g["items"] as $it) {
            $unidades += (float) $it["cantidad"];
        }
        $partes = explode("-", $g["documento"]);
        $docCsv = $partes[0] . "-" . $partes[1];
        $f = date("d/m/Y", strtotime($g["fecha_emision"]));

        // FILA 1 — misma estructura que ctrGenerarFEFacBolA; E/F/N/BH/BI/BJ en 0.00, Y total gratuito, AO IGV informativo
        $v1 = array(
            "A" => $f, "B" => $docCsv, "C" => $g["tipo_doc"], "D" => "PEN",
            "E" => "0.00", "F" => "0.00", "G" => "PEN",
            "N" => "0.00",
            "Q" => "0101",
            "Y" => self::m($total),
            "Z" => "0.00", "AL" => "0.00",
            "AS" => $n, "AT" => "1",
            "BH" => "0.00", "BI" => "0.00", "BJ" => "0.00",
        );
        if ($esFactura) {
            $v1["O"] = "CONTADO";
        }
        if ($gravado) {
            $v1["AO"] = self::m($igv);
        }
        $fila1 = self::fila(83, $v1);

        $fila2 = self::fila(36, array());
        $fila3 = $esFactura ? self::fila(8, array("H" => "ATTACH_DOC")) : self::fila(6, array());

        // FILA 4 — emisor (mismos valores que mdlFEFacturaCabA)
        $fila4 = implode(",", array(
            "Corporacion Vasco S.A.C.", "JACKY FORM", self::RUC_EMISOR, "", "CAL.SANTO TORIBIO NRO. 259",
            "URB.SANTA LUISA 1RA ETAPA", "LIMA", "LIMA", "SAN MARTIN DE PORRES", "PE", "FINANZCO", "josecorpo", "0002", "",
        ));

        // FILA 5 — receptor
        $sinUbigeo = empty($g["receptor_ubigeo"]);
        $fila5 = implode(",", array(
            $g["receptor_doc"], $g["receptor_tipo_doc"], $g["receptor_nombre"], "",
            $sinUbigeo ? "" : $g["receptor_ubigeo"],
            $g["receptor_direccion"] !== "" ? $g["receptor_direccion"] : "-", "-",
            (string) $g["receptor_dpto"], (string) $g["receptor_prov"], (string) $g["receptor_dist"],
            "PE", (string) $g["receptor_email"], "",
        ));

        // FILA 6 — A monto en letras; B leyenda 1002 (gratuita)
        $letras = "CERO Y 00/100 SOLES"; // el importe total de una operación gratuita siempre es 0.00
        $fila6 = self::fila(57, array(
            "A" => $letras,
            "B" => "TRANSFERENCIA GRATUITA DE UN BIEN Y/O SERVICIO PRESTADO GRATUITAMENTE",
        ));

        // FILA 7 — A observación (o unidades), D–G adicionales de impresión como en una venta normal
        $obs = $g["observaciones"] !== null && $g["observaciones"] !== "" ? $g["observaciones"] : $g["motivo"];
        $fila7 = self::fila(13, array(
            "A" => "Nro.unidades: " . rtrim(rtrim(number_format($unidades, 3, ".", ""), "0"), ".") . " " . $obs,
            "D" => $g["receptor_doc"],
            "E" => "GRATUITA " . $g["categoria"],
            "F" => "0.00",
            "G" => $g["motivo"],
        ));

        $lineas = array($fila1, $fila2, $fila3, $fila4, $fila5, $fila6, $fila7);

        // Ítems — mismas columnas que mdlFEFacturaDetA; cambian E, G, H, I, J, K, L, M, T, U, W, X, AP
        $i = 0;
        foreach ($g["items"] as $it) {
            $i++;
            $vv = self::m($it["valor_venta"]);
            $igvL = $gravado ? self::m($it["igv_informativo"]) : "0.00";
            $lineas[] = self::fila(63, array(
                "A" => $i, "B" => $it["unidad_cod"],
                "C" => number_format((float) $it["cantidad"], 3, ".", ""),
                "D" => $it["descripcion"],
                "E" => "0.00", "F" => "01",
                "G" => self::m($it["valor_ref_unit"]), "H" => "02",
                "I" => $igvL, "J" => $igvL,
                "K" => $g["afectacion"], "L" => "9996", "M" => $gravado ? "18" : "0",
                "S" => $it["codigo"],
                "T" => "0.00", "U" => $vv,
                "X" => $vv,
                "AP" => "0.00",
            ));
        }
        $lineas[] = "FF00FF";
        return array("lineas" => $lineas, "nombre" => self::RUC_EMISOR . "-" . $g["tipo_doc"] . "-" . $docCsv);
    }

    private static function armarArchivo($id)
    {
        $g = ModeloComprobantesEspeciales::mdlObtener((int) $id);
        if (!$g) {
            return array("ok" => false, "msg" => "Comprobante no encontrado.");
        }
        if ($g["estado"] === "ANULADO") {
            return array("ok" => false, "msg" => "El comprobante está ANULADO.");
        }
        $afect = self::afectaciones(isset($g["tipo_operacion"]) && $g["tipo_operacion"] !== "" ? $g["tipo_operacion"] : self::TIPO_DEFECTO);
        if (!isset($afect[$g["afectacion"]])) {
            return array("ok" => false, "msg" => "La afectación " . $g["afectacion"] . " no está habilitada.");
        }
        try {
            $a = self::armarLineas($g);
        } catch (RuntimeException $e) {
            return array("ok" => false, "msg" => $e->getMessage());
        }
        return array("ok" => true, "g" => $g, "lineas" => $a["lineas"], "nombre" => $a["nombre"]);
    }

    /** Contenido del CSV para revisarlo: no lo escribe en ninguna carpeta ni cambia el estado. */
    public static function ctrVerArchivo($id)
    {
        $a = self::armarArchivo($id);
        if (!$a["ok"]) {
            return $a;
        }
        return array("ok" => true, "nombre" => $a["nombre"] . ".csv", "contenido" => implode(PHP_EOL, $a["lineas"]));
    }

    private static function rutaFisica($ruta)
    {
        if (preg_match('#^([A-Za-z]:|/|\\\\)#', $ruta)) {
            return $ruta;
        }
        return __DIR__ . "/" . $ruta;
    }

    /** Deja el CSV en la carpeta que vigila eFact y marca ENVIADO. */
    public static function ctrEnviar($id)
    {
        $a = self::armarArchivo($id);
        if (!$a["ok"]) {
            return $a;
        }
        if ($a["g"]["estado"] !== "GENERADO") {
            return array("ok" => false, "msg" => "El comprobante ya está " . $a["g"]["estado"] . ".");
        }
        require_once __DIR__ . "/config.php";
        $nombre = $a["nombre"];
        $origenDir = self::rutaFisica(ORIGEN);
        $destinoDir = self::rutaFisica($a["g"]["tipo_doc"] === "01" ? DESTINO_INVOICE : DESTINO_BOLETA);
        if (!is_dir($origenDir)) {
            @mkdir($origenDir, 0775, true);
        }
        $tmp = $origenDir . $nombre . ".txt";
        if (file_put_contents($tmp, implode(PHP_EOL, $a["lineas"])) === false) {
            return array("ok" => false, "msg" => "No se pudo escribir el archivo temporal.");
        }
        if (!@rename($tmp, $destinoDir . $nombre . ".csv")) {
            @unlink($tmp);
            return array("ok" => false, "msg" => "No se pudo dejar el CSV en la carpeta de eFact.");
        }
        ModeloComprobantesEspeciales::mdlMarcarEnviado((int) $id, $nombre . ".csv");
        return array("ok" => true, "documento" => $a["g"]["documento"], "archivo" => $nombre . ".csv");
    }
}
