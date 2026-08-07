<?php

require_once __DIR__ . "/permisos-modulos.config.php";

class ControladorHelpdesk
{
    const SECTOR = "ti";
    const MODULO = "helpdesk";

    const TIPOS = array(
        "INCIDENCIA",
        "REQUERIMIENTO",
        "CONSULTA",
        "OTRO",
        "DESARROLLO",
        "CORRECCION",
    );
    const PRIORIDADES = array("BAJA", "MEDIA", "ALTA");
    const ESTADOS = array("ABIERTO", "EN_PROGRESO", "ESPERANDO_USUARIO", "CERRADO");

    const ADJUNTO_MAX_BYTES = 10485760;
    const ADJUNTO_MAX_CANTIDAD = 5;
    const ADJUNTO_DIR = "vistas/img/helpdesk";
    /** IDs fijos que pueden figurar en "Asignar a" */
    const AGENTES_ASIGNABLES = array(6, 10);

    public static function ctrCargarCatalogoJson()
    {
        static $catalogo = null;
        if ($catalogo !== null) {
            return $catalogo;
        }

        $ruta = __DIR__ . "/helpdesk-catalogo.json";
        $defaults = array(
            "areas" => array("Ventas", "TI", "Otro"),
            "sistemas" => array(
                array(
                    "id" => "VASCORP",
                    "nombre" => "Vascorp",
                    "ayuda" => "ERP interno",
                    "modulos" => array(
                        array("seccion" => "Otros", "items" => array("Otro")),
                    ),
                    "modulos_valores" => array("Otro"),
                ),
            ),
            "modulos_valores" => array("Otro"),
        );

        if (!is_readable($ruta)) {
            $catalogo = $defaults;
            return $catalogo;
        }

        $raw = file_get_contents($ruta);
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $catalogo = $defaults;
            return $catalogo;
        }

        $areas = array();
        if (!empty($data["areas"]) && is_array($data["areas"])) {
            foreach ($data["areas"] as $a) {
                $a = trim((string) $a);
                if ($a !== "") {
                    $areas[] = $a;
                }
            }
        }

        $parseGrupos = function ($gruposRaw) {
            $modulosGrupos = array();
            $modulosValores = array();
            if (!is_array($gruposRaw)) {
                return array($modulosGrupos, $modulosValores);
            }

            $esPlano = true;
            foreach ($gruposRaw as $entry) {
                if (is_array($entry)) {
                    $esPlano = false;
                    break;
                }
            }

            if ($esPlano) {
                $items = array();
                foreach ($gruposRaw as $m) {
                    $m = trim((string) $m);
                    if ($m !== "") {
                        $items[] = $m;
                        $modulosValores[] = $m;
                    }
                }
                if (!empty($items)) {
                    $modulosGrupos[] = array("seccion" => "Módulos", "items" => $items);
                }
                return array($modulosGrupos, $modulosValores);
            }

            foreach ($gruposRaw as $grupo) {
                if (!is_array($grupo)) {
                    continue;
                }
                $seccion = isset($grupo["seccion"]) ? trim((string) $grupo["seccion"]) : "";
                if ($seccion === "") {
                    $seccion = "Otros";
                }
                $items = array();
                if (!empty($grupo["items"]) && is_array($grupo["items"])) {
                    foreach ($grupo["items"] as $m) {
                        $m = trim((string) $m);
                        if ($m === "") {
                            continue;
                        }
                        $items[] = $m;
                        $modulosValores[] = $m;
                    }
                }
                if (!empty($items)) {
                    $modulosGrupos[] = array(
                        "seccion" => $seccion,
                        "items" => $items,
                    );
                }
            }

            return array($modulosGrupos, $modulosValores);
        };

        $sistemas = array();
        $todosValores = array();

        if (!empty($data["sistemas"]) && is_array($data["sistemas"])) {
            foreach ($data["sistemas"] as $sis) {
                if (!is_array($sis)) {
                    continue;
                }
                $id = isset($sis["id"]) ? trim((string) $sis["id"]) : "";
                $nombre = isset($sis["nombre"]) ? trim((string) $sis["nombre"]) : "";
                if ($id === "" || $nombre === "") {
                    continue;
                }
                list($grupos, $valores) = $parseGrupos(isset($sis["modulos"]) ? $sis["modulos"] : array());
                $valores = array_values(array_unique($valores));
                $sistemas[] = array(
                    "id" => $id,
                    "nombre" => $nombre,
                    "ayuda" => isset($sis["ayuda"]) ? trim((string) $sis["ayuda"]) : "",
                    "modulos" => $grupos,
                    "modulos_valores" => $valores,
                );
                foreach ($valores as $v) {
                    $todosValores[] = $v;
                }
            }
        } elseif (!empty($data["modulos"]) && is_array($data["modulos"])) {
            // Legado: un solo bloque como Vascorp
            list($grupos, $valores) = $parseGrupos($data["modulos"]);
            $valores = array_values(array_unique($valores));
            $sistemas[] = array(
                "id" => "VASCORP",
                "nombre" => "Vascorp",
                "ayuda" => "ERP interno",
                "modulos" => $grupos,
                "modulos_valores" => $valores,
            );
            $todosValores = $valores;
        }

        $todosValores = array_values(array_unique($todosValores));

        $catalogo = array(
            "areas" => !empty($areas) ? $areas : $defaults["areas"],
            "sistemas" => !empty($sistemas) ? $sistemas : $defaults["sistemas"],
            "modulos_valores" => !empty($todosValores) ? $todosValores : $defaults["modulos_valores"],
        );

        return $catalogo;
    }

    public static function ctrPuede($accion = "ver")
    {
        return function_exists("usuarioPuedeModulo")
            && usuarioPuedeModulo(self::SECTOR, self::MODULO, $accion);
    }

    public static function ctrUsuarioSesionId()
    {
        return isset($_SESSION["id"]) ? (int) $_SESSION["id"] : 0;
    }

    public static function ctrPermisos()
    {
        return array(
            "ver" => self::ctrPuede("ver"),
            "registrar" => self::ctrPuede("registrar"),
            "gestionar" => self::ctrPuede("gestionar"),
        );
    }

    public static function ctrCatalogos()
    {
        $json = self::ctrCargarCatalogoJson();

        return array(
            "tipos" => self::TIPOS,
            "prioridades" => self::PRIORIDADES,
            "estados" => self::ESTADOS,
            "areas" => $json["areas"],
            "sistemas" => $json["sistemas"],
        );
    }

    private static function sistemaPorId($sistemaId)
    {
        $json = self::ctrCargarCatalogoJson();
        foreach ($json["sistemas"] as $sis) {
            if ($sis["id"] === $sistemaId) {
                return $sis;
            }
        }

        return null;
    }

    private static function puedeVerTicket($ticket)
    {
        if (!$ticket) {
            return false;
        }
        if (self::ctrPuede("gestionar")) {
            return true;
        }
        $uid = self::ctrUsuarioSesionId();

        return ((int) $ticket["solicitante_id"] === $uid)
            || ((int) $ticket["creado_por_id"] === $uid);
    }

    public static function ctrListar()
    {
        if (!self::ctrPuede("ver")) {
            return array("ok" => false, "msg" => "Sin permiso.");
        }

        $filtros = array(
            "estado" => isset($_POST["estado"]) ? trim((string) $_POST["estado"]) : "",
            "tipo" => isset($_POST["tipo"]) ? trim((string) $_POST["tipo"]) : "",
            "prioridad" => isset($_POST["prioridad"]) ? trim((string) $_POST["prioridad"]) : "",
            "area" => isset($_POST["area"]) ? trim((string) $_POST["area"]) : "",
            "asignado_id" => isset($_POST["asignado_id"]) ? (int) $_POST["asignado_id"] : 0,
            "q" => isset($_POST["q"]) ? trim((string) $_POST["q"]) : "",
            "solo_abiertos" => !empty($_POST["solo_abiertos"]),
        );

        if (!self::ctrPuede("gestionar")) {
            $filtros["solicitante_id"] = self::ctrUsuarioSesionId();
        } elseif (!empty($_POST["solicitante_id"])) {
            $filtros["solicitante_id"] = (int) $_POST["solicitante_id"];
        }

        if ($filtros["estado"] !== "" && !in_array($filtros["estado"], self::ESTADOS, true)) {
            $filtros["estado"] = "";
        }
        if ($filtros["tipo"] !== "" && !in_array($filtros["tipo"], self::TIPOS, true)) {
            $filtros["tipo"] = "";
        }
        if ($filtros["prioridad"] !== "" && !in_array($filtros["prioridad"], self::PRIORIDADES, true)) {
            $filtros["prioridad"] = "";
        }
        if (!empty($filtros["area"])) {
            $json = self::ctrCargarCatalogoJson();
            if (!in_array($filtros["area"], $json["areas"], true)) {
                $filtros["area"] = "";
            }
        }

        return array(
            "ok" => true,
            "items" => ModeloHelpdesk::mdlListar($filtros),
            "permisos" => self::ctrPermisos(),
        );
    }

    public static function ctrVer()
    {
        if (!self::ctrPuede("ver")) {
            return array("ok" => false, "msg" => "Sin permiso.");
        }

        $id = isset($_POST["id"]) ? (int) $_POST["id"] : (isset($_GET["id"]) ? (int) $_GET["id"] : 0);
        $ticket = ModeloHelpdesk::mdlObtener($id);
        if (!$ticket || !self::puedeVerTicket($ticket)) {
            return array("ok" => false, "msg" => "Ticket no encontrado.");
        }

        return array(
            "ok" => true,
            "ticket" => $ticket,
            "comentarios" => ModeloHelpdesk::mdlListarComentarios($id),
            "adjuntos" => ModeloHelpdesk::mdlListarAdjuntos($id),
            "permisos" => self::ctrPermisos(),
            "catalogos" => self::ctrCatalogos(),
            "agentes" => self::ctrPuede("gestionar") ? self::agentes() : array(),
        );
    }

    /**
     * Pule asunto, descripción y pasos con OpenAI (prompt en archivo).
     */
    public static function ctrPulirTexto()
    {
        if (!self::ctrPuede("registrar") && !self::ctrPuede("gestionar")) {
            return array("ok" => false, "msg" => "Sin permiso.");
        }

        $titulo = isset($_POST["titulo"]) ? trim((string) $_POST["titulo"]) : "";
        $descripcion = isset($_POST["descripcion"]) ? trim((string) $_POST["descripcion"]) : "";
        $pasos = isset($_POST["pasos_reproducir"]) ? trim((string) $_POST["pasos_reproducir"]) : "";

        if ($titulo === "" && $descripcion === "" && $pasos === "") {
            return array("ok" => false, "msg" => "Escriba al menos el asunto o la descripción.");
        }

        $apiKey = defined("OPENAI_API_KEY") ? trim((string) OPENAI_API_KEY) : "";
        if ($apiKey === "") {
            return array("ok" => false, "msg" => "Configure OPENAI_API_KEY en controladores/config.php");
        }

        $promptPath = __DIR__ . "/helpdesk-prompt-pulir.txt";
        if (!is_readable($promptPath)) {
            return array("ok" => false, "msg" => "No se encontró el archivo de prompt.");
        }
        $systemPrompt = trim((string) file_get_contents($promptPath));
        if ($systemPrompt === "") {
            return array("ok" => false, "msg" => "El prompt está vacío.");
        }

        $userPayload = array(
            "titulo" => $titulo,
            "descripcion" => $descripcion,
            "pasos_reproducir" => $pasos,
        );
        $userMsg = "Mejora este ticket y responde solo con el JSON pedido:\n"
            . json_encode($userPayload, JSON_UNESCAPED_UNICODE);

        $modelo = defined("OPENAI_IC_MODELO") && trim((string) OPENAI_IC_MODELO) !== ""
            ? trim((string) OPENAI_IC_MODELO)
            : "gpt-4o-mini";

        $payload = json_encode(array(
            "model" => $modelo,
            "temperature" => 0.2,
            "max_tokens" => 1200,
            "response_format" => array("type" => "json_object"),
            "messages" => array(
                array("role" => "system", "content" => $systemPrompt),
                array("role" => "user", "content" => $userMsg),
            ),
        ), JSON_UNESCAPED_UNICODE);

        $ch = curl_init("https://api.openai.com/v1/chat/completions");
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Authorization: Bearer " . $apiKey,
            ),
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_CONNECTTIMEOUT => 15,
        ));

        $respuesta = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            return array("ok" => false, "msg" => "Error de conexión con OpenAI: " . $error);
        }

        $json = json_decode($respuesta, true);
        if ($httpCode < 200 || $httpCode >= 300) {
            $detalle = isset($json["error"]["message"]) ? $json["error"]["message"] : ("HTTP " . $httpCode);
            return array("ok" => false, "msg" => "OpenAI: " . $detalle);
        }

        $content = isset($json["choices"][0]["message"]["content"])
            ? trim((string) $json["choices"][0]["message"]["content"])
            : "";
        if ($content === "") {
            return array("ok" => false, "msg" => "Respuesta vacía de OpenAI.");
        }

        // Por si el modelo envuelve en ```json
        if (preg_match('/\{.*\}/s', $content, $m)) {
            $content = $m[0];
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return array("ok" => false, "msg" => "No se pudo interpretar la respuesta de la IA.");
        }

        $outTitulo = isset($data["titulo"]) ? trim((string) $data["titulo"]) : $titulo;
        $outDesc = isset($data["descripcion"]) ? trim((string) $data["descripcion"]) : $descripcion;
        $outPasos = isset($data["pasos_reproducir"]) ? trim((string) $data["pasos_reproducir"]) : $pasos;

        if ($outTitulo === "" && $titulo !== "") {
            $outTitulo = $titulo;
        }
        if ($outDesc === "" && $descripcion !== "") {
            $outDesc = $descripcion;
        }
        if (strlen($outTitulo) > 200) {
            $outTitulo = substr($outTitulo, 0, 200);
        }

        return array(
            "ok" => true,
            "msg" => "Texto pulido.",
            "titulo" => $outTitulo,
            "descripcion" => $outDesc,
            "pasos_reproducir" => $outPasos,
            "modelo" => $modelo,
        );
    }

    public static function ctrCrear()
    {
        if (!self::ctrPuede("registrar")) {
            return array("ok" => false, "msg" => "Sin permiso para registrar.");
        }

        $titulo = isset($_POST["titulo"]) ? trim((string) $_POST["titulo"]) : "";
        $descripcion = isset($_POST["descripcion"]) ? trim((string) $_POST["descripcion"]) : "";
        $pasos = isset($_POST["pasos_reproducir"]) ? trim((string) $_POST["pasos_reproducir"]) : "";
        $tipo = isset($_POST["tipo"]) ? trim((string) $_POST["tipo"]) : "";
        $prioridad = isset($_POST["prioridad"]) ? trim((string) $_POST["prioridad"]) : "MEDIA";
        $area = isset($_POST["area"]) ? trim((string) $_POST["area"]) : "";
        $modulo = isset($_POST["modulo"]) ? trim((string) $_POST["modulo"]) : "";
        $sistema = isset($_POST["sistema"]) ? trim((string) $_POST["sistema"]) : "";
        $uid = self::ctrUsuarioSesionId();
        $catalogo = self::ctrCargarCatalogoJson();
        $sisInfo = self::sistemaPorId($sistema);

        // Contacto desde sesión (sin pedir al usuario)
        $correo = isset($_SESSION["correo"]) ? trim((string) $_SESSION["correo"]) : "";
        if (strlen($correo) > 120) {
            $correo = substr($correo, 0, 120);
        }

        $solicitanteId = $uid;
        if (self::ctrPuede("gestionar") && !empty($_POST["solicitante_id"])) {
            $solicitanteId = (int) $_POST["solicitante_id"];
        }

        // Asignación solo con permiso gestionar; solo IDs 6 y 10
        $asignadoId = null;
        if (self::ctrPuede("gestionar") && isset($_POST["asignado_id"]) && $_POST["asignado_id"] !== "") {
            $asignadoId = (int) $_POST["asignado_id"];
            if ($asignadoId < 1 || !in_array($asignadoId, self::AGENTES_ASIGNABLES, true)) {
                return array("ok" => false, "msg" => "Asignado inválido.");
            }
        }

        if ($titulo === "" || strlen($titulo) > 200) {
            return array("ok" => false, "msg" => "Asunto inválido.");
        }
        if ($descripcion === "") {
            return array("ok" => false, "msg" => "Descripción requerida.");
        }
        if (!in_array($tipo, self::TIPOS, true)) {
            return array("ok" => false, "msg" => "Tipo inválido.");
        }
        if (!in_array($prioridad, self::PRIORIDADES, true)) {
            return array("ok" => false, "msg" => "Prioridad inválida.");
        }
        if ($area === "" || !in_array($area, $catalogo["areas"], true)) {
            return array("ok" => false, "msg" => "Área requerida.");
        }
        if (!$sisInfo) {
            return array("ok" => false, "msg" => "Sistema requerido.");
        }
        if ($modulo !== "" && !in_array($modulo, $sisInfo["modulos_valores"], true)) {
            return array("ok" => false, "msg" => "Módulo inválido para el sistema elegido.");
        }

        $archivosValidados = self::validarArchivosEntrada();
        if (!empty($archivosValidados["error"])) {
            return array("ok" => false, "msg" => $archivosValidados["error"]);
        }

        $id = ModeloHelpdesk::mdlCrear(array(
            "titulo" => $titulo,
            "descripcion" => $descripcion,
            "pasos_reproducir" => ($pasos === "" ? null : $pasos),
            "tipo" => $tipo,
            "prioridad" => $prioridad,
            "modulo" => ($modulo === "" ? null : $modulo),
            "sistema" => $sistema,
            "area" => $area,
            "correo_contacto" => ($correo === "" ? null : $correo),
            "telefono_contacto" => null,
            "canal_preferido" => null,
            "solicitante_id" => $solicitanteId,
            "asignado_id" => $asignadoId,
            "creado_por_id" => $uid,
        ));

        if ($id < 1) {
            return array("ok" => false, "msg" => "No se pudo crear el ticket.");
        }

        ModeloHelpdesk::mdlAgregarComentario(array(
            "ticket_id" => $id,
            "usuario_id" => $uid,
            "tipo_evento" => "ALTA",
            "mensaje" => "Ticket creado.",
            "estado_anterior" => null,
            "estado_nuevo" => "ABIERTO",
        ));

        $adjuntosOk = 0;
        foreach ($archivosValidados["files"] as $file) {
            if (self::guardarAdjunto($id, $uid, $file)) {
                $adjuntosOk++;
            }
        }

        $msg = "Ticket #" . $id . " creado.";
        if ($adjuntosOk > 0) {
            $msg .= " Adjuntos: " . $adjuntosOk . ".";
        }

        return array("ok" => true, "id" => $id, "msg" => $msg);
    }

    private static function validarArchivosEntrada()
    {
        $out = array("files" => array(), "error" => "");
        if (empty($_FILES["adjuntos"])) {
            return $out;
        }

        $raw = $_FILES["adjuntos"];
        $lista = array();

        if (is_array($raw["name"])) {
            $n = count($raw["name"]);
            for ($i = 0; $i < $n; $i++) {
                if ($raw["error"][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $lista[] = array(
                    "name" => $raw["name"][$i],
                    "type" => $raw["type"][$i],
                    "tmp_name" => $raw["tmp_name"][$i],
                    "error" => $raw["error"][$i],
                    "size" => $raw["size"][$i],
                );
            }
        } else {
            if ($raw["error"] !== UPLOAD_ERR_NO_FILE) {
                $lista[] = $raw;
            }
        }

        if (count($lista) > self::ADJUNTO_MAX_CANTIDAD) {
            $out["error"] = "Máximo " . self::ADJUNTO_MAX_CANTIDAD . " archivos.";
            return $out;
        }

        $extOk = array("jpg", "jpeg", "png", "pdf", "doc", "docx", "xls", "xlsx");
        foreach ($lista as $file) {
            if ($file["error"] !== UPLOAD_ERR_OK) {
                $out["error"] = "Error al subir un archivo.";
                return $out;
            }
            if ((int) $file["size"] > self::ADJUNTO_MAX_BYTES) {
                $out["error"] = "Cada archivo debe pesar máximo 10 MB.";
                return $out;
            }
            $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
            if (!in_array($ext, $extOk, true)) {
                $out["error"] = "Formato no permitido: " . $ext;
                return $out;
            }
            if (!is_uploaded_file($file["tmp_name"])) {
                $out["error"] = "Archivo inválido.";
                return $out;
            }
            $out["files"][] = $file;
        }

        return $out;
    }

    private static function guardarAdjunto($ticketId, $usuarioId, $file)
    {
        $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $nombreGuardado = $ticketId . "_" . uniqid("", true) . "." . $ext;
        $dirRel = self::ADJUNTO_DIR;
        $dirAbs = dirname(__DIR__) . "/" . $dirRel;

        if (!is_dir($dirAbs)) {
            @mkdir($dirAbs, 0755, true);
        }

        $destino = $dirAbs . "/" . $nombreGuardado;
        if (!move_uploaded_file($file["tmp_name"], $destino)) {
            return false;
        }

        $idAdj = ModeloHelpdesk::mdlAgregarAdjunto(array(
            "ticket_id" => $ticketId,
            "nombre_original" => $file["name"],
            "nombre_guardado" => $nombreGuardado,
            "mime" => isset($file["type"]) ? $file["type"] : null,
            "tamanio" => (int) $file["size"],
            "usuario_id" => $usuarioId,
        ));

        if ($idAdj < 1) {
            @unlink($destino);
            return false;
        }

        return true;
    }

    public static function ctrDescargarAdjunto()
    {
        if (!self::ctrPuede("ver")) {
            return array("ok" => false, "msg" => "Sin permiso.");
        }

        $id = isset($_GET["id"]) ? (int) $_GET["id"] : (isset($_POST["id"]) ? (int) $_POST["id"] : 0);
        $adj = ModeloHelpdesk::mdlObtenerAdjunto($id);
        if (!$adj) {
            return array("ok" => false, "msg" => "Adjunto no encontrado.");
        }

        $ticket = ModeloHelpdesk::mdlObtener((int) $adj["ticket_id"]);
        if (!$ticket || !self::puedeVerTicket($ticket)) {
            return array("ok" => false, "msg" => "Sin permiso para este adjunto.");
        }

        $path = dirname(__DIR__) . "/" . self::ADJUNTO_DIR . "/" . $adj["nombre_guardado"];
        if (!is_file($path)) {
            return array("ok" => false, "msg" => "Archivo no encontrado en disco.");
        }

        return array(
            "ok" => true,
            "path" => $path,
            "nombre" => $adj["nombre_original"],
            "mime" => $adj["mime"] ? $adj["mime"] : "application/octet-stream",
        );
    }

    public static function ctrComentar()
    {
        if (!self::ctrPuede("ver")) {
            return array("ok" => false, "msg" => "Sin permiso.");
        }

        $id = isset($_POST["id"]) ? (int) $_POST["id"] : 0;
        $mensaje = isset($_POST["mensaje"]) ? trim((string) $_POST["mensaje"]) : "";
        $ticket = ModeloHelpdesk::mdlObtener($id);

        if (!$ticket || !self::puedeVerTicket($ticket)) {
            return array("ok" => false, "msg" => "Ticket no encontrado.");
        }
        if ($mensaje === "") {
            return array("ok" => false, "msg" => "Escriba un comentario.");
        }

        $ok = ModeloHelpdesk::mdlAgregarComentario(array(
            "ticket_id" => $id,
            "usuario_id" => self::ctrUsuarioSesionId(),
            "tipo_evento" => "COMENTARIO",
            "mensaje" => $mensaje,
            "estado_anterior" => null,
            "estado_nuevo" => null,
        ));

        if (!$ok) {
            return array("ok" => false, "msg" => "No se pudo guardar el comentario.");
        }

        return array("ok" => true, "msg" => "Comentario agregado.");
    }

    public static function ctrActualizar()
    {
        if (!self::ctrPuede("gestionar")) {
            return array("ok" => false, "msg" => "Sin permiso para gestionar.");
        }

        $id = isset($_POST["id"]) ? (int) $_POST["id"] : 0;
        $ticket = ModeloHelpdesk::mdlObtener($id);
        if (!$ticket) {
            return array("ok" => false, "msg" => "Ticket no encontrado.");
        }

        $uid = self::ctrUsuarioSesionId();
        $campos = array();
        $eventos = array();

        if (isset($_POST["estado"]) && $_POST["estado"] !== "") {
            $estado = trim((string) $_POST["estado"]);
            if (!in_array($estado, self::ESTADOS, true)) {
                return array("ok" => false, "msg" => "Estado inválido.");
            }
            if ($estado !== $ticket["estado"]) {
                $campos["estado"] = $estado;
                if ($estado === "CERRADO") {
                    $campos["cerrado_en"] = date("Y-m-d H:i:s");
                } elseif ($ticket["estado"] === "CERRADO") {
                    $campos["cerrado_en"] = null;
                }
                $eventos[] = array(
                    "tipo_evento" => "CAMBIO_ESTADO",
                    "mensaje" => "Estado: " . $ticket["estado"] . " → " . $estado,
                    "estado_anterior" => $ticket["estado"],
                    "estado_nuevo" => $estado,
                );
            }
        }

        if (array_key_exists("asignado_id", $_POST)) {
            $asignado = $_POST["asignado_id"] === "" || $_POST["asignado_id"] === null
                ? null
                : (int) $_POST["asignado_id"];
            if ($asignado !== null && !in_array($asignado, self::AGENTES_ASIGNABLES, true)) {
                return array("ok" => false, "msg" => "Asignado inválido.");
            }
            $actual = $ticket["asignado_id"] === null ? null : (int) $ticket["asignado_id"];
            if ($asignado !== $actual) {
                $campos["asignado_id"] = $asignado;
                $eventos[] = array(
                    "tipo_evento" => "ASIGNACION",
                    "mensaje" => $asignado
                        ? ("Asignado a usuario #" . $asignado)
                        : "Sin asignar",
                    "estado_anterior" => null,
                    "estado_nuevo" => null,
                );
            }
        }

        if (isset($_POST["prioridad"]) && $_POST["prioridad"] !== "") {
            $prioridad = trim((string) $_POST["prioridad"]);
            if (!in_array($prioridad, self::PRIORIDADES, true)) {
                return array("ok" => false, "msg" => "Prioridad inválida.");
            }
            if ($prioridad !== $ticket["prioridad"]) {
                $campos["prioridad"] = $prioridad;
                $eventos[] = array(
                    "tipo_evento" => "COMENTARIO",
                    "mensaje" => "Prioridad: " . $ticket["prioridad"] . " → " . $prioridad,
                    "estado_anterior" => null,
                    "estado_nuevo" => null,
                );
            }
        }

        if (isset($_POST["tipo"]) && $_POST["tipo"] !== "") {
            $tipo = trim((string) $_POST["tipo"]);
            if (!in_array($tipo, self::TIPOS, true)) {
                return array("ok" => false, "msg" => "Tipo inválido.");
            }
            if ($tipo !== $ticket["tipo"]) {
                $campos["tipo"] = $tipo;
            }
        }

        if (empty($campos)) {
            return array("ok" => false, "msg" => "Sin cambios.");
        }

        if (!ModeloHelpdesk::mdlActualizar($id, $campos)) {
            return array("ok" => false, "msg" => "No se pudo actualizar.");
        }

        foreach ($eventos as $ev) {
            ModeloHelpdesk::mdlAgregarComentario(array(
                "ticket_id" => $id,
                "usuario_id" => $uid,
                "tipo_evento" => $ev["tipo_evento"],
                "mensaje" => $ev["mensaje"],
                "estado_anterior" => $ev["estado_anterior"],
                "estado_nuevo" => $ev["estado_nuevo"],
            ));
        }

        if (isset($_POST["nota"]) && trim((string) $_POST["nota"]) !== "") {
            ModeloHelpdesk::mdlAgregarComentario(array(
                "ticket_id" => $id,
                "usuario_id" => $uid,
                "tipo_evento" => "COMENTARIO",
                "mensaje" => trim((string) $_POST["nota"]),
                "estado_anterior" => null,
                "estado_nuevo" => null,
            ));
        }

        return array("ok" => true, "msg" => "Ticket actualizado.");
    }

    public static function ctrAgentes()
    {
        if (!self::ctrPuede("gestionar") && !self::ctrPuede("registrar") && !self::ctrPuede("ver")) {
            return array("ok" => false, "msg" => "Sin permiso.");
        }

        return array(
            "ok" => true,
            "agentes" => self::agentes(),
            "usuarios" => self::ctrPuede("gestionar")
                ? ModeloHelpdesk::mdlListarUsuariosActivos(300)
                : array(),
            "catalogos" => self::ctrCatalogos(),
            "permisos" => self::ctrPermisos(),
            "contacto" => array(
                "correo" => isset($_SESSION["correo"]) ? (string) $_SESSION["correo"] : "",
                "nombre" => isset($_SESSION["nombre"]) ? (string) $_SESSION["nombre"] : "",
            ),
        );
    }

    private static function agentes()
    {
        return ModeloHelpdesk::mdlUsuariosPorIds(self::AGENTES_ASIGNABLES);
    }
}
