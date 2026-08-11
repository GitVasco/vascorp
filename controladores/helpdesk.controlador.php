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
        "DESARROLLO",
        "CORRECCION",
        "OTRO",
    );
    const PRIORIDADES = array("BAJA", "MEDIA", "ALTA");
    const ESTADOS = array("ABIERTO", "EN_PROGRESO", "ESPERANDO_USUARIO", "CERRADO");

    const ADJUNTO_MAX_BYTES = 10485760;
    const ADJUNTO_MAX_CANTIDAD = 5;
    /** Relativo al proyecto; se usa si HELPDESK_ADJUNTOS_DIR no está definido o vacío */
    const ADJUNTO_DIR = "vistas/img/helpdesk";
    /** IDs fijos que pueden figurar en "Asignar a" */
    const AGENTES_ASIGNABLES = array(6, 10);
    /** Control total: ve todos los tickets, reabre, IA, etc. */
    const USUARIO_CONTROL_TOTAL = 6;
    /** Solo este usuario puede usar Corregir / Pulir con IA */
    const USUARIO_PULIR_IA = 6;
    /** Solo este usuario puede reabrir tickets cerrados (panel TI, sin plazo) */
    const USUARIO_REABRIR = 6;
    /** Solo este usuario puede eliminar tickets (borrado definitivo) */
    const USUARIO_ELIMINAR = 6;
    /** Días tras el cierre en que el solicitante puede reabrir con comentario */
    const DIAS_REAPERTURA_SOLICITANTE = 7;
    /** Horas límite SLA por prioridad (horas laborales hasta el cierre) */
    const SLA_HORAS = array(
        "ALTA" => 4,
        "MEDIA" => 24,
        "BAJA" => 72,
    );
    /**
     * Horario laboral para el reloj SLA (segundos desde medianoche).
     * Lun–Vie 08:00–17:30 · Sáb 08:00–12:15 · Dom sin atención.
     */
    const SLA_HORARIO = array(
        1 => array(28800, 63000),  // lun 08:00–17:30
        2 => array(28800, 63000),
        3 => array(28800, 63000),
        4 => array(28800, 63000),
        5 => array(28800, 63000),
        6 => array(28800, 44100),  // sáb 08:00–12:15
        // 7 domingo: sin ventana
    );
    /** Solo desarrollo largo: sin reloj SLA de cierre */
    const TIPOS_SIN_SLA = array("DESARROLLO");
    /** Tipos que admiten fecha estimada de entrega (compromiso, no SLA) */
    const TIPOS_FECHA_ESTIMADA = array("DESARROLLO");

    /**
     * Normaliza fecha estimada (Y-m-d) o null. Solo aplica a tipos planificados.
     */
    private static function normalizarFechaEstimada($raw, $tipo)
    {
        if (!in_array($tipo, self::TIPOS_FECHA_ESTIMADA, true)) {
            return null;
        }
        $raw = trim((string) $raw);
        if ($raw === "") {
            return null;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return false;
        }
        $parts = explode("-", $raw);
        if (!checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
            return false;
        }

        return $raw;
    }

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

    public static function ctrPuedePulirIa()
    {
        return self::ctrUsuarioSesionId() === self::USUARIO_PULIR_IA;
    }

    public static function ctrPuedeReabrir()
    {
        return self::ctrUsuarioSesionId() === self::USUARIO_REABRIR;
    }

    public static function ctrPuedeEliminar()
    {
        return self::ctrUsuarioSesionId() === self::USUARIO_ELIMINAR;
    }

    /**
     * Info de reapertura por solicitante (plazo de DIAS_REAPERTURA_SOLICITANTE tras cerrado_en).
     */
    public static function ctrInfoReaperturaSolicitante($ticket)
    {
        $dias = self::DIAS_REAPERTURA_SOLICITANTE;
        $uid = self::ctrUsuarioSesionId();
        $esSolicitante = $ticket
            && ((int) $ticket["solicitante_id"] === $uid);

        $out = array(
            "es_solicitante" => $esSolicitante,
            "puede_reabrir" => false,
            "dias" => $dias,
            "hasta" => null,
            "vencida" => false,
        );

        if (!$ticket || !$esSolicitante || $ticket["estado"] !== "CERRADO") {
            return $out;
        }

        // Preferir cerrado_en; si falta (cierres viejos), usar actualizado_en.
        $fechaRef = "";
        if (!empty($ticket["cerrado_en"])) {
            $fechaRef = $ticket["cerrado_en"];
        } elseif (!empty($ticket["actualizado_en"])) {
            $fechaRef = $ticket["actualizado_en"];
        }

        if ($fechaRef === "") {
            // Cerrado sin fecha usable: permitir reabrir dentro del plazo desde ahora.
            $out["puede_reabrir"] = true;
            $out["hasta"] = date("Y-m-d H:i:s", time() + ($dias * 86400));
            return $out;
        }

        $ts = strtotime($fechaRef);
        if ($ts === false) {
            $out["puede_reabrir"] = true;
            $out["hasta"] = date("Y-m-d H:i:s", time() + ($dias * 86400));
            return $out;
        }

        $hastaTs = $ts + ($dias * 86400);
        $out["hasta"] = date("Y-m-d H:i:s", $hastaTs);
        if (time() <= $hastaTs) {
            $out["puede_reabrir"] = true;
        } else {
            $out["vencida"] = true;
        }

        return $out;
    }

    /** Joel: ve y gestiona todos los tickets. */
    public static function ctrEsControlTotal()
    {
        return self::ctrUsuarioSesionId() === self::USUARIO_CONTROL_TOTAL;
    }

    /**
     * Agente con gestionar pero sin control total (p. ej. Kennedy):
     * bandeja = asignados a él + sin asignar.
     */
    public static function ctrEsAgenteBandeja()
    {
        return self::ctrPuede("gestionar") && !self::ctrEsControlTotal();
    }

    /**
     * Ventana laboral del día ISO (1=lun … 7=dom): [inicio, fin) en segundos desde medianoche.
     * @return array{0:int,1:int}|null
     */
    private static function ventanaLaboralDia($isoDia)
    {
        $isoDia = (int) $isoDia;
        $horario = self::SLA_HORARIO;
        return array_key_exists($isoDia, $horario) ? $horario[$isoDia] : null;
    }

    /**
     * Si $ts está fuera de horario, lo mueve al próximo instante laboral.
     */
    private static function alinearAHorarioLaboral($ts)
    {
        $ts = (int) $ts;
        for ($i = 0; $i < 16; $i++) {
            $day = strtotime(date("Y-m-d", $ts) . " 00:00:00");
            if ($day === false) {
                return $ts;
            }
            $win = self::ventanaLaboralDia((int) date("N", $day));
            if ($win !== null) {
                $open = $day + (int) $win[0];
                $close = $day + (int) $win[1];
                if ($ts < $open) {
                    return $open;
                }
                if ($ts < $close) {
                    return $ts;
                }
            }
            $next = strtotime("+1 day", $day);
            if ($next === false) {
                return $ts;
            }
            $ts = $next;
        }

        return $ts;
    }

    /**
     * Segundos laborales entre dos timestamps (noches / domingos / fuera de turno no cuentan).
     */
    private static function segundosLaboralesEntre($from, $to)
    {
        $from = (int) $from;
        $to = (int) $to;
        if ($to <= $from) {
            return 0;
        }

        $total = 0;
        $day = strtotime(date("Y-m-d", $from) . " 00:00:00");
        $endDay = strtotime(date("Y-m-d", $to) . " 00:00:00");
        if ($day === false || $endDay === false) {
            return 0;
        }

        for ($guard = 0; $day <= $endDay && $guard < 800; $guard++) {
            $win = self::ventanaLaboralDia((int) date("N", $day));
            if ($win !== null) {
                $open = $day + (int) $win[0];
                $close = $day + (int) $win[1];
                $a = max($from, $open);
                $b = min($to, $close);
                if ($b > $a) {
                    $total += ($b - $a);
                }
            }
            $next = strtotime("+1 day", $day);
            if ($next === false) {
                break;
            }
            $day = $next;
        }

        return $total;
    }

    /**
     * Avanza $segundos laborales desde $from y devuelve el timestamp resultante.
     */
    private static function agregarSegundosLaborales($from, $segundos)
    {
        $left = (int) $segundos;
        if ($left <= 0) {
            return (int) $from;
        }

        $t = self::alinearAHorarioLaboral((int) $from);
        for ($guard = 0; $left > 0 && $guard < 800; $guard++) {
            $day = strtotime(date("Y-m-d", $t) . " 00:00:00");
            if ($day === false) {
                break;
            }
            $win = self::ventanaLaboralDia((int) date("N", $day));
            if ($win === null) {
                $t = self::alinearAHorarioLaboral(strtotime("+1 day", $day));
                continue;
            }
            $close = $day + (int) $win[1];
            $avail = $close - $t;
            if ($avail <= 0) {
                $t = self::alinearAHorarioLaboral($close);
                continue;
            }
            if ($left <= $avail) {
                return $t + $left;
            }
            $left -= $avail;
            $t = self::alinearAHorarioLaboral($close);
        }

        return $t;
    }

    private static function formatearDuracionSla($seg)
    {
        $seg = (int) abs($seg);
        $h = (int) floor($seg / 3600);
        $m = (int) floor(($seg % 3600) / 60);
        if ($h > 0) {
            return $h . "h " . $m . "m";
        }
        return max(1, $m) . "m";
    }

    /**
     * Calcula estado SLA de un ticket (reloj en horario laboral).
     * Desarrollo y tickets exentos no aplican SLA de cierre.
     * @return array{codigo:string,label:string,cls:string,horas_limite:int,deadline:string|null,segundos:int}
     */
    public static function ctrSlaDeTicket($ticket)
    {
        if (!empty($ticket["sla_exento"]) && (int) $ticket["sla_exento"] === 1) {
            $motivo = isset($ticket["sla_exento_motivo"]) ? trim((string) $ticket["sla_exento_motivo"]) : "";
            return array(
                "codigo" => "EXENTO",
                "label" => "SLA cancelado",
                "cls" => "hd-sla-exento",
                "horas_limite" => 0,
                "deadline" => null,
                "segundos" => 0,
                "motivo" => $motivo,
            );
        }

        $tipo = isset($ticket["tipo"]) ? $ticket["tipo"] : "";
        if (in_array($tipo, self::TIPOS_SIN_SLA, true)) {
            return array(
                "codigo" => "N/A",
                "label" => "Sin SLA",
                "cls" => "hd-sla-na",
                "horas_limite" => 0,
                "deadline" => null,
                "segundos" => 0,
            );
        }

        $prioridad = isset($ticket["prioridad"]) ? $ticket["prioridad"] : "MEDIA";
        $slaMap = self::SLA_HORAS;
        $horas = isset($slaMap[$prioridad]) ? (int) $slaMap[$prioridad] : 24;
        $creado = !empty($ticket["creado_en"]) ? strtotime($ticket["creado_en"]) : false;
        if ($creado === false) {
            return array(
                "codigo" => "N/A",
                "label" => "Sin fecha",
                "cls" => "hd-sla-na",
                "horas_limite" => $horas,
                "deadline" => null,
                "segundos" => 0,
            );
        }

        $limiteSeg = $horas * 3600;
        $cerrado = isset($ticket["estado"]) && $ticket["estado"] === "CERRADO";
        $fin = $cerrado && !empty($ticket["cerrado_en"])
            ? strtotime($ticket["cerrado_en"])
            : time();
        if ($fin === false) {
            $fin = time();
        }

        $usado = self::segundosLaboralesEntre($creado, $fin);
        $diff = $limiteSeg - $usado;
        $deadline = self::agregarSegundosLaborales($creado, $limiteSeg);
        $deadlineStr = date("Y-m-d H:i:s", $deadline);

        if ($cerrado) {
            if ($diff >= 0) {
                return array(
                    "codigo" => "CUMPLIDO",
                    "label" => "Cumplido",
                    "cls" => "hd-sla-ok",
                    "horas_limite" => $horas,
                    "deadline" => $deadlineStr,
                    "segundos" => $diff,
                );
            }
            return array(
                "codigo" => "FUERA",
                "label" => "Fuera de SLA · " . self::formatearDuracionSla($diff),
                "cls" => "hd-sla-fuera",
                "horas_limite" => $horas,
                "deadline" => $deadlineStr,
                "segundos" => abs($diff),
            );
        }

        if ($diff >= 0) {
            $urgente = ($diff <= (int) floor($limiteSeg * 0.25));
            return array(
                "codigo" => "RESTANTE",
                "label" => self::formatearDuracionSla($diff) . " restante",
                "cls" => $urgente ? "hd-sla-aviso" : "hd-sla-restante",
                "horas_limite" => $horas,
                "deadline" => $deadlineStr,
                "segundos" => $diff,
            );
        }

        return array(
            "codigo" => "VENCIDO",
            "label" => "Vencido · " . self::formatearDuracionSla($diff),
            "cls" => "hd-sla-vencido",
            "horas_limite" => $horas,
            "deadline" => $deadlineStr,
            "segundos" => abs($diff),
        );
    }

    /**
     * Códigos que entran al % / gráfico de cumplimiento SLA.
     * N/A (sin SLA) y EXENTO (cancelado) no cuentan.
     */
    private static function slaCuentaCumplimiento($codigo)
    {
        return $codigo === "CUMPLIDO" || $codigo === "FUERA";
    }

    /**
     * Cuenta abiertos vencidos con el mismo reloj laboral que el badge SLA.
     * Excluye desarrollo y SLA cancelado (vía candidatos del modelo).
     */
    private static function ctrContarVencidosSla($filtros)
    {
        $candidatos = ModeloHelpdesk::mdlCandidatosSlaAbiertos($filtros);
        $n = 0;
        foreach ($candidatos as $t) {
            $sla = self::ctrSlaDeTicket($t);
            if (isset($sla["codigo"]) && $sla["codigo"] === "VENCIDO") {
                $n++;
            }
        }

        return $n;
    }

    public static function ctrEnriquecerSla($items)
    {
        if (!is_array($items)) {
            return array();
        }
        foreach ($items as &$t) {
            $t["sla"] = self::ctrSlaDeTicket($t);
        }
        unset($t);
        return $items;
    }

    public static function ctrPermisos()
    {
        return array(
            "ver" => self::ctrPuede("ver"),
            "registrar" => self::ctrPuede("registrar"),
            "gestionar" => self::ctrPuede("gestionar"),
            "control_total" => self::ctrEsControlTotal(),
            "agente_bandeja" => self::ctrEsAgenteBandeja(),
            "pulir_ia" => self::ctrPuedePulirIa(),
            "reabrir" => self::ctrPuedeReabrir(),
            "eliminar" => self::ctrPuedeEliminar(),
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
        if (self::ctrEsControlTotal()) {
            return true;
        }
        if (self::ctrPuede("gestionar")) {
            $aid = isset($ticket["asignado_id"]) ? $ticket["asignado_id"] : null;
            if ($aid === null || $aid === "" || (int) $aid === 0) {
                return true;
            }

            return (int) $aid === self::ctrUsuarioSesionId();
        }
        $uid = self::ctrUsuarioSesionId();

        return ((int) $ticket["solicitante_id"] === $uid)
            || ((int) $ticket["creado_por_id"] === $uid);
    }

    /** Alcance de listado/KPIs de bandeja (no aplica a indicadores globales). */
    private static function aplicarAlcanceBandeja(&$filtros)
    {
        if (self::ctrEsControlTotal()) {
            return;
        }
        if (self::ctrPuede("gestionar")) {
            $filtros["asignado_mio_o_libre"] = self::ctrUsuarioSesionId();
            return;
        }
        $filtros["solicitante_id"] = self::ctrUsuarioSesionId();
    }

    /**
     * Tickets no cerrados para badge del navbar (solo agentes con gestionar).
     * Control total: todos. Agente bandeja: asignados a él + sin asignar.
     */
    public static function ctrContarAbiertosNavbar()
    {
        if (!self::ctrPuede("gestionar")) {
            return array("ok" => false, "abiertos" => 0);
        }

        $filtros = array();
        self::aplicarAlcanceBandeja($filtros);
        $resumen = ModeloHelpdesk::mdlResumen($filtros);

        return array(
            "ok" => true,
            "abiertos" => isset($resumen["activos"]) ? (int) $resumen["activos"] : 0,
            "control_total" => self::ctrEsControlTotal(),
        );
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
            "q" => isset($_POST["q"]) ? trim((string) $_POST["q"]) : "",
            "solo_abiertos" => !empty($_POST["solo_abiertos"]),
            "solo_vencidos" => !empty($_POST["solo_vencidos"]),
        );

        // Filtros por persona: solo gestionar (Joel/Kennedy). El alcance de bandeja se aplica después.
        if (self::ctrPuede("gestionar")) {
            if (!empty($_POST["solicitante_id"])) {
                $filtros["solicitante_id"] = (int) $_POST["solicitante_id"];
            }
            if (isset($_POST["asignado_id"]) && $_POST["asignado_id"] !== "") {
                $asigRaw = trim((string) $_POST["asignado_id"]);
                if ($asigRaw === "__SIN__") {
                    $filtros["asignado_libre"] = true;
                } else {
                    $filtros["asignado_id"] = (int) $asigRaw;
                }
            }
        }

        self::aplicarAlcanceBandeja($filtros);

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

        $filtrosResumen = $filtros;
        unset($filtrosResumen["estado"], $filtrosResumen["solo_abiertos"], $filtrosResumen["solo_vencidos"]);

        if (!empty($filtros["solo_vencidos"])) {
            $filtros["solo_abiertos"] = true;
            $filtros["estado"] = "";
        }

        $items = ModeloHelpdesk::mdlListar($filtros);
        $items = self::ctrEnriquecerSla($items);

        if (!empty($filtros["solo_vencidos"])) {
            $items = array_values(array_filter($items, function ($t) {
                return isset($t["sla"]["codigo"]) && $t["sla"]["codigo"] === "VENCIDO";
            }));
        }

        $resumen = ModeloHelpdesk::mdlResumen($filtrosResumen);
        $resumen["vencidos"] = self::ctrContarVencidosSla($filtrosResumen);

        return array(
            "ok" => true,
            "items" => $items,
            "resumen" => $resumen,
            "sla_horas" => self::SLA_HORAS,
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
            "sla" => self::ctrSlaDeTicket($ticket),
            "sla_horas" => self::SLA_HORAS,
            "comentarios" => ModeloHelpdesk::mdlListarComentarios($id),
            "adjuntos" => ModeloHelpdesk::mdlListarAdjuntos($id),
            "permisos" => self::ctrPermisos(),
            "catalogos" => self::ctrCatalogos(),
            "agentes" => self::ctrPuede("gestionar") ? self::agentes() : array(),
            "reapertura_solicitante" => self::ctrInfoReaperturaSolicitante($ticket),
        );
    }

    /**
     * Dashboard de indicadores (gráficos + tablas) para el período.
     */
    public static function ctrIndicadores()
    {
        if (!self::ctrPuede("gestionar")) {
            return array("ok" => false, "msg" => "Sin permiso.");
        }

        $hoy = date("Y-m-d");
        $desde = isset($_POST["desde"]) ? trim((string) $_POST["desde"]) : date("Y-m-01");
        $hasta = isset($_POST["hasta"]) ? trim((string) $_POST["hasta"]) : $hoy;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
            $desde = date("Y-m-01");
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            $hasta = $hoy;
        }
        if ($desde > $hasta) {
            $tmp = $desde;
            $desde = $hasta;
            $hasta = $tmp;
        }

        // hasta exclusivo al día siguiente
        $hastaExcl = date("Y-m-d", strtotime($hasta . " +1 day"));

        // Dashboard global del área (Joel y Kennedy)
        $filtros = array();
        if (self::ctrEsControlTotal() && !empty($_POST["asignado_id"])) {
            $filtros["asignado_id"] = (int) $_POST["asignado_id"];
        }

        $tickets = ModeloHelpdesk::mdlListarParaIndicadores($desde, $hastaExcl, $filtros);

        $labelsTipo = array(
            "INCIDENCIA" => "Incidencia",
            "REQUERIMIENTO" => "Requerimiento",
            "CONSULTA" => "Consulta",
            "OTRO" => "Otro",
            "DESARROLLO" => "Desarrollo",
            "CORRECCION" => "Corrección",
        );
        $labelsEstado = array(
            "ABIERTO" => "Abierto",
            "EN_PROGRESO" => "En progreso",
            "ESPERANDO_USUARIO" => "Esperando",
            "CERRADO" => "Cerrado",
        );
        $labelsSistema = array(
            "VASCORP" => "Vascorp",
            "SISTEMA_VASCO" => "Sistema Vasco",
            "VASCOPRO" => "VascoPro",
            "TI_EMPRESA" => "TI / Soporte",
        );

        $creados = 0;
        $cerradosPeriodo = 0;
        $abiertosAhora = 0;
        $slaOk = 0;
        $slaFuera = 0;
        $sumaHorasResolucion = 0;
        $nResolucion = 0;
        $resPorPri = array();
        $backlogEdad = array(
            "0-2 días" => 0,
            "3-7 días" => 0,
            "8-15 días" => 0,
            "Más de 15 días" => 0,
        );

        $porDia = array();
        $porTipo = array();
        $porPrioridad = array();
        $porSistema = array();
        $porEstado = array();
        $porArea = array();
        $porModulo = array();
        $porAsignado = array();
        $vencidos = array();
        $cerradosDetalle = array();

        $iniTs = strtotime($desde . " 00:00:00");
        $finTs = strtotime($hastaExcl . " 00:00:00");

        foreach ($tickets as $t) {
            $creadoTs = !empty($t["creado_en"]) ? strtotime($t["creado_en"]) : false;
            $cerradoTs = !empty($t["cerrado_en"]) ? strtotime($t["cerrado_en"]) : false;
            $enPeriodoCreado = ($creadoTs !== false && $creadoTs >= $iniTs && $creadoTs < $finTs);
            $enPeriodoCerrado = ($cerradoTs !== false && $cerradoTs >= $iniTs && $cerradoTs < $finTs);
            $estaAbierto = ($t["estado"] !== "CERRADO");
            $sla = self::ctrSlaDeTicket($t);

            if ($enPeriodoCreado) {
                $creados++;
                $dia = date("Y-m-d", $creadoTs);
                if (!isset($porDia[$dia])) {
                    $porDia[$dia] = 0;
                }
                $porDia[$dia]++;

                $tipo = isset($t["tipo"]) ? $t["tipo"] : "OTRO";
                $porTipo[$tipo] = isset($porTipo[$tipo]) ? $porTipo[$tipo] + 1 : 1;

                $pri = isset($t["prioridad"]) ? $t["prioridad"] : "MEDIA";
                $porPrioridad[$pri] = isset($porPrioridad[$pri]) ? $porPrioridad[$pri] + 1 : 1;

                $sis = !empty($t["sistema"]) ? $t["sistema"] : "SIN_SISTEMA";
                $porSistema[$sis] = isset($porSistema[$sis]) ? $porSistema[$sis] + 1 : 1;

                $area = !empty($t["area"]) ? $t["area"] : "Sin área";
                $porArea[$area] = isset($porArea[$area]) ? $porArea[$area] + 1 : 1;

                $mod = !empty($t["modulo"]) ? $t["modulo"] : "Sin módulo";
                $porModulo[$mod] = isset($porModulo[$mod]) ? $porModulo[$mod] + 1 : 1;
            }

            if ($estaAbierto) {
                $abiertosAhora++;
                $est = $t["estado"];
                $porEstado[$est] = isset($porEstado[$est]) ? $porEstado[$est] + 1 : 1;

                if ($sla["codigo"] === "VENCIDO") {
                    $vencidos[] = array(
                        "id" => (int) $t["id"],
                        "titulo" => $t["titulo"],
                        "prioridad" => $t["prioridad"],
                        "estado" => $t["estado"],
                        "asignado_nombre" => $t["asignado_nombre"],
                        "solicitante_nombre" => $t["solicitante_nombre"],
                        "creado_en" => $t["creado_en"],
                        "sla" => $sla,
                    );
                }
            }

            if ($enPeriodoCerrado) {
                $cerradosPeriodo++;
                // Solo CUMPLIDO / FUERA; Sin SLA y cancelado no bajan el %.
                if (self::slaCuentaCumplimiento($sla["codigo"])) {
                    if ($sla["codigo"] === "CUMPLIDO") {
                        $slaOk++;
                    } else {
                        $slaFuera++;
                    }
                }
                if ($creadoTs !== false && $cerradoTs !== false && $cerradoTs >= $creadoTs) {
                    $horas = ($cerradoTs - $creadoTs) / 3600;
                    $sumaHorasResolucion += $horas;
                    $nResolucion++;
                    $pri = isset($t["prioridad"]) ? $t["prioridad"] : "MEDIA";
                    if (!isset($resPorPri[$pri])) {
                        $resPorPri[$pri] = array("suma" => 0, "n" => 0);
                    }
                    $resPorPri[$pri]["suma"] += $horas;
                    $resPorPri[$pri]["n"]++;
                }
                $cerradosDetalle[] = array(
                    "id" => (int) $t["id"],
                    "titulo" => $t["titulo"],
                    "prioridad" => $t["prioridad"],
                    "asignado_nombre" => $t["asignado_nombre"],
                    "cerrado_en" => $t["cerrado_en"],
                    "sla" => $sla,
                );
            }

            if ($estaAbierto && $creadoTs !== false) {
                $diasAbiertos = (int) floor((time() - $creadoTs) / 86400);
                if ($diasAbiertos <= 2) {
                    $backlogEdad["0-2 días"]++;
                } elseif ($diasAbiertos <= 7) {
                    $backlogEdad["3-7 días"]++;
                } elseif ($diasAbiertos <= 15) {
                    $backlogEdad["8-15 días"]++;
                } else {
                    $backlogEdad["Más de 15 días"]++;
                }
            }

            // Carga por asignado: creados, abiertos o cerrados del período
            if ($enPeriodoCreado || $estaAbierto || $enPeriodoCerrado) {
                $aid = $t["asignado_id"] === null || $t["asignado_id"] === "" ? 0 : (int) $t["asignado_id"];
                $anombre = $aid > 0
                    ? ($t["asignado_nombre"] ? $t["asignado_nombre"] : ("#" . $aid))
                    : "Sin asignar";
                if (!isset($porAsignado[$aid])) {
                    $porAsignado[$aid] = array(
                        "id" => $aid,
                        "nombre" => $anombre,
                        "creados" => 0,
                        "abiertos" => 0,
                        "cerrados" => 0,
                        "vencidos" => 0,
                        "sla_ok" => 0,
                        "sla_fuera" => 0,
                    );
                }
                if ($enPeriodoCreado) {
                    $porAsignado[$aid]["creados"]++;
                }
                if ($estaAbierto) {
                    $porAsignado[$aid]["abiertos"]++;
                    if ($sla["codigo"] === "VENCIDO") {
                        $porAsignado[$aid]["vencidos"]++;
                    }
                }
                if ($enPeriodoCerrado) {
                    $porAsignado[$aid]["cerrados"]++;
                    if (self::slaCuentaCumplimiento($sla["codigo"])) {
                        if ($sla["codigo"] === "CUMPLIDO") {
                            $porAsignado[$aid]["sla_ok"]++;
                        } else {
                            $porAsignado[$aid]["sla_fuera"]++;
                        }
                    }
                }
            }
        }

        ksort($porDia);
        arsort($porModulo);
        $topModulos = array();
        $i = 0;
        foreach ($porModulo as $nombre => $n) {
            $topModulos[] = array("nombre" => $nombre, "total" => $n);
            $i++;
            if ($i >= 10) {
                break;
            }
        }

        $mapSerie = function ($arr, $labelMap = null) {
            $labels = array();
            $data = array();
            foreach ($arr as $k => $v) {
                $labels[] = ($labelMap && isset($labelMap[$k])) ? $labelMap[$k] : $k;
                $data[] = (int) $v;
            }
            return array("labels" => $labels, "data" => $data);
        };

        $pctSla = ($slaOk + $slaFuera) > 0
            ? round(($slaOk * 100) / ($slaOk + $slaFuera), 1)
            : null;
        $promedioHoras = $nResolucion > 0
            ? round($sumaHorasResolucion / $nResolucion, 1)
            : null;

        $resolucionPrioridad = array();
        foreach (array("ALTA", "MEDIA", "BAJA") as $pri) {
            if (!empty($resPorPri[$pri]["n"])) {
                $resolucionPrioridad[] = array(
                    "prioridad" => $pri,
                    "horas" => round($resPorPri[$pri]["suma"] / $resPorPri[$pri]["n"], 1),
                    "n" => (int) $resPorPri[$pri]["n"],
                );
            } else {
                $resolucionPrioridad[] = array(
                    "prioridad" => $pri,
                    "horas" => null,
                    "n" => 0,
                );
            }
        }

        // Comparar con período anterior de igual duración
        $diasPeriodo = (int) max(1, round(($finTs - $iniTs) / 86400));
        $prevHasta = date("Y-m-d", strtotime($desde . " -1 day"));
        $prevDesde = date("Y-m-d", strtotime($prevHasta . " -" . ($diasPeriodo - 1) . " days"));
        $prevHastaExcl = date("Y-m-d", strtotime($prevHasta . " +1 day"));
        $ticketsPrev = ModeloHelpdesk::mdlListarParaIndicadores($prevDesde, $prevHastaExcl, $filtros);
        $prevIni = strtotime($prevDesde . " 00:00:00");
        $prevFin = strtotime($prevHastaExcl . " 00:00:00");
        $pCreados = 0;
        $pCerrados = 0;
        $pSlaOk = 0;
        $pSlaFuera = 0;
        $pSumaH = 0;
        $pN = 0;
        foreach ($ticketsPrev as $t) {
            $cTs = !empty($t["creado_en"]) ? strtotime($t["creado_en"]) : false;
            $xTs = !empty($t["cerrado_en"]) ? strtotime($t["cerrado_en"]) : false;
            if ($cTs !== false && $cTs >= $prevIni && $cTs < $prevFin) {
                $pCreados++;
            }
            if ($xTs !== false && $xTs >= $prevIni && $xTs < $prevFin) {
                $pCerrados++;
                $slaP = self::ctrSlaDeTicket($t);
                if (self::slaCuentaCumplimiento($slaP["codigo"])) {
                    if ($slaP["codigo"] === "CUMPLIDO") {
                        $pSlaOk++;
                    } else {
                        $pSlaFuera++;
                    }
                }
                if ($cTs !== false && $xTs >= $cTs) {
                    $pSumaH += ($xTs - $cTs) / 3600;
                    $pN++;
                }
            }
        }
        $pSlaPct = ($pSlaOk + $pSlaFuera) > 0
            ? round(($pSlaOk * 100) / ($pSlaOk + $pSlaFuera), 1)
            : null;
        $pProm = $pN > 0 ? round($pSumaH / $pN, 1) : null;

        $deltaPct = function ($actual, $anterior) {
            if ($anterior === null || $anterior === 0) {
                return null;
            }
            return round((($actual - $anterior) * 100) / $anterior, 1);
        };

        usort($vencidos, function ($a, $b) {
            return $b["sla"]["segundos"] - $a["sla"]["segundos"];
        });
        usort($cerradosDetalle, function ($a, $b) {
            return strcmp($b["cerrado_en"], $a["cerrado_en"]);
        });

        foreach ($porAsignado as &$asigRow) {
            $totSla = $asigRow["sla_ok"] + $asigRow["sla_fuera"];
            $asigRow["sla_pct"] = $totSla > 0
                ? round(($asigRow["sla_ok"] * 100) / $totSla, 1)
                : null;
        }
        unset($asigRow);

        $actividad = ModeloHelpdesk::mdlActividadReciente(12, $filtros);

        return array(
            "ok" => true,
            "periodo" => array(
                "desde" => $desde,
                "hasta" => $hasta,
                "prev_desde" => $prevDesde,
                "prev_hasta" => $prevHasta,
            ),
            "sla_horas" => self::SLA_HORAS,
            "kpis" => array(
                "creados" => $creados,
                "cerrados" => $cerradosPeriodo,
                "abiertos" => $abiertosAhora,
                "vencidos" => count($vencidos),
                "sla_ok" => $slaOk,
                "sla_fuera" => $slaFuera,
                "sla_pct" => $pctSla,
                "promedio_horas" => $promedioHoras,
                "delta" => array(
                    "creados" => $deltaPct($creados, $pCreados),
                    "cerrados" => $deltaPct($cerradosPeriodo, $pCerrados),
                    "sla_pct" => ($pctSla !== null && $pSlaPct !== null)
                        ? round($pctSla - $pSlaPct, 1)
                        : null,
                    "promedio_horas" => ($promedioHoras !== null && $pProm !== null)
                        ? round($promedioHoras - $pProm, 1)
                        : null,
                ),
            ),
            "charts" => array(
                "por_dia" => array(
                    "labels" => array_keys($porDia),
                    "data" => array_values($porDia),
                ),
                "por_tipo" => $mapSerie($porTipo, $labelsTipo),
                "por_prioridad" => $mapSerie($porPrioridad),
                "por_sistema" => $mapSerie($porSistema, $labelsSistema + array("SIN_SISTEMA" => "Sin sistema")),
                "por_estado" => $mapSerie($porEstado, $labelsEstado),
                "por_area" => $mapSerie($porArea),
                "backlog_edad" => array(
                    "labels" => array_keys($backlogEdad),
                    "data" => array_values($backlogEdad),
                ),
                "sla" => array(
                    "labels" => array("Dentro de SLA", "Fuera de SLA"),
                    "data" => array($slaOk, $slaFuera),
                ),
            ),
            "tablas" => array(
                "asignados" => array_values($porAsignado),
                "top_modulos" => $topModulos,
                "resolucion_prioridad" => $resolucionPrioridad,
                "vencidos" => array_slice($vencidos, 0, 15),
                "cerrados" => array_slice($cerradosDetalle, 0, 12),
                "actividad" => $actividad,
            ),
            "permisos" => self::ctrPermisos(),
        );
    }

    /**
     * Pule asunto/descripción/pasos (alta) o mensaje de respuesta con OpenAI.
     * POST modo=respuesta → solo corrige el campo mensaje.
     */
    public static function ctrPulirTexto()
    {
        if (!self::ctrPuedePulirIa()) {
            return array("ok" => false, "msg" => "Sin permiso para corregir con IA.");
        }

        $modo = isset($_POST["modo"]) ? trim((string) $_POST["modo"]) : "";
        $esRespuesta = ($modo === "respuesta");

        $titulo = isset($_POST["titulo"]) ? trim((string) $_POST["titulo"]) : "";
        $descripcion = isset($_POST["descripcion"]) ? trim((string) $_POST["descripcion"]) : "";
        $pasos = isset($_POST["pasos_reproducir"]) ? trim((string) $_POST["pasos_reproducir"]) : "";
        $mensaje = isset($_POST["mensaje"]) ? trim((string) $_POST["mensaje"]) : "";

        if ($esRespuesta) {
            if ($mensaje === "") {
                return array("ok" => false, "msg" => "Escriba la respuesta a corregir.");
            }
        } elseif ($titulo === "" && $descripcion === "" && $pasos === "") {
            return array("ok" => false, "msg" => "Escriba al menos el asunto o la descripción.");
        }

        $apiKey = defined("OPENAI_API_KEY") ? trim((string) OPENAI_API_KEY) : "";
        if ($apiKey === "") {
            return array("ok" => false, "msg" => "Configure OPENAI_API_KEY en controladores/config.php");
        }

        $promptPath = $esRespuesta
            ? (__DIR__ . "/helpdesk-prompt-pulir-respuesta.txt")
            : (__DIR__ . "/helpdesk-prompt-pulir.txt");
        if (!is_readable($promptPath)) {
            return array("ok" => false, "msg" => "No se encontró el archivo de prompt.");
        }
        $systemPrompt = trim((string) file_get_contents($promptPath));
        if ($systemPrompt === "") {
            return array("ok" => false, "msg" => "El prompt está vacío.");
        }

        if ($esRespuesta) {
            $userMsg = "Mejora esta respuesta de helpdesk y responde solo con el JSON pedido:\n"
                . json_encode(array("mensaje" => $mensaje), JSON_UNESCAPED_UNICODE);
            $maxTokens = 800;
        } else {
            $userMsg = "Mejora este ticket y responde solo con el JSON pedido:\n"
                . json_encode(array(
                    "titulo" => $titulo,
                    "descripcion" => $descripcion,
                    "pasos_reproducir" => $pasos,
                ), JSON_UNESCAPED_UNICODE);
            $maxTokens = 1200;
        }

        $modelo = defined("OPENAI_IC_MODELO") && trim((string) OPENAI_IC_MODELO) !== ""
            ? trim((string) OPENAI_IC_MODELO)
            : "gpt-4o-mini";

        $payload = json_encode(array(
            "model" => $modelo,
            "temperature" => 0.2,
            "max_tokens" => $maxTokens,
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

        if (preg_match('/\{.*\}/s', $content, $m)) {
            $content = $m[0];
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return array("ok" => false, "msg" => "No se pudo interpretar la respuesta de la IA.");
        }

        if ($esRespuesta) {
            $outMsg = isset($data["mensaje"]) ? trim((string) $data["mensaje"]) : $mensaje;
            if ($outMsg === "") {
                $outMsg = $mensaje;
            }
            return array(
                "ok" => true,
                "msg" => "Respuesta corregida.",
                "mensaje" => $outMsg,
                "modelo" => $modelo,
            );
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
            "msg" => "Asunto, descripción y pasos pulidos.",
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

        // Fecha estimada la define TI al gestionar, no al crear.
        $fechaEstimada = null;

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
            "fecha_estimada" => $fechaEstimada,
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

    /**
     * Carpeta absoluta de adjuntos.
     * Prioridad: HELPDESK_ADJUNTOS_DIR (config.php) → vistas/img/helpdesk del proyecto.
     */
    private static function dirAdjuntosAbs()
    {
        if (defined("HELPDESK_ADJUNTOS_DIR")) {
            $custom = trim((string) constant("HELPDESK_ADJUNTOS_DIR"));
            if ($custom !== "") {
                return rtrim(str_replace("\\", "/", $custom), "/");
            }
        }

        return str_replace("\\", "/", dirname(__DIR__) . "/" . self::ADJUNTO_DIR);
    }

    private static function pathAdjuntoAbs($nombreGuardado)
    {
        $nombre = basename((string) $nombreGuardado);
        return self::dirAdjuntosAbs() . "/" . $nombre;
    }

    private static function guardarAdjunto($ticketId, $usuarioId, $file)
    {
        $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $nombreGuardado = $ticketId . "_" . uniqid("", true) . "." . $ext;
        $dirAbs = self::dirAdjuntosAbs();

        if (!is_dir($dirAbs)) {
            if (!@mkdir($dirAbs, 0755, true) && !is_dir($dirAbs)) {
                return false;
            }
        }

        $destino = self::pathAdjuntoAbs($nombreGuardado);
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

        $path = self::pathAdjuntoAbs($adj["nombre_guardado"]);
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
        if ($ticket["estado"] === "CERRADO") {
            return array(
                "ok" => false,
                "msg" => "Ticket cerrado. Reábralo cambiando el estado para poder responder.",
            );
        }

        $valAdj = self::validarArchivosEntrada();
        if ($valAdj["error"] !== "") {
            return array("ok" => false, "msg" => $valAdj["error"]);
        }
        $files = $valAdj["files"];

        if ($mensaje === "" && empty($files)) {
            return array("ok" => false, "msg" => "Escriba un comentario o adjunte un archivo.");
        }
        if ($mensaje === "") {
            $n = count($files);
            $mensaje = $n === 1
                ? "(Se adjuntó 1 archivo.)"
                : "(Se adjuntaron " . $n . " archivos.)";
        }

        $uid = self::ctrUsuarioSesionId();
        $ok = ModeloHelpdesk::mdlAgregarComentario(array(
            "ticket_id" => $id,
            "usuario_id" => $uid,
            "tipo_evento" => "COMENTARIO",
            "mensaje" => $mensaje,
            "estado_anterior" => null,
            "estado_nuevo" => null,
        ));

        if (!$ok) {
            return array("ok" => false, "msg" => "No se pudo guardar el comentario.");
        }

        $adjuntosOk = 0;
        foreach ($files as $file) {
            if (self::guardarAdjunto($id, $uid, $file)) {
                $adjuntosOk++;
            }
        }

        // Opcional: agente cambia estado al responder
        if (self::ctrPuede("gestionar") && isset($_POST["cambiar_estado"]) && trim((string) $_POST["cambiar_estado"]) !== "") {
            $estado = trim((string) $_POST["cambiar_estado"]);
            if (in_array($estado, self::ESTADOS, true) && $estado !== $ticket["estado"]) {
                $campos = array("estado" => $estado);
                if ($estado === "CERRADO") {
                    $campos["cerrado_en"] = date("Y-m-d H:i:s");
                } elseif ($ticket["estado"] === "CERRADO") {
                    $campos["cerrado_en"] = null;
                }
                if (ModeloHelpdesk::mdlActualizar($id, $campos)) {
                    ModeloHelpdesk::mdlAgregarComentario(array(
                        "ticket_id" => $id,
                        "usuario_id" => $uid,
                        "tipo_evento" => "CAMBIO_ESTADO",
                        "mensaje" => "Estado: " . $ticket["estado"] . " → " . $estado,
                        "estado_anterior" => $ticket["estado"],
                        "estado_nuevo" => $estado,
                    ));
                }
            }
        }

        $msg = "Respuesta enviada.";
        if ($adjuntosOk > 0) {
            $msg .= " Adjuntos: " . $adjuntosOk . ".";
        }

        return array("ok" => true, "msg" => $msg);
    }

    /**
     * El solicitante reabre un ticket cerrado dentro del plazo, con comentario obligatorio.
     * Pasa a EN_PROGRESO y queda visible de nuevo en la bandeja de TI.
     */
    public static function ctrReabrirSolicitante()
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

        $info = self::ctrInfoReaperturaSolicitante($ticket);
        if (empty($info["puede_reabrir"])) {
            if (!empty($info["es_solicitante"]) && !empty($info["vencida"])) {
                return array(
                    "ok" => false,
                    "msg" => "El plazo de " . self::DIAS_REAPERTURA_SOLICITANTE
                        . " días para reabrir ya venció. Abrí un ticket nuevo si necesitás ayuda.",
                );
            }
            return array("ok" => false, "msg" => "No podés reabrir este ticket.");
        }

        if ($mensaje === "" || strlen($mensaje) < 5) {
            return array(
                "ok" => false,
                "msg" => "Explicá qué falta o qué no quedó resuelto (mínimo unas palabras).",
            );
        }
        if (strlen($mensaje) > 4000) {
            $mensaje = substr($mensaje, 0, 4000);
        }

        $uid = self::ctrUsuarioSesionId();
        $ok = ModeloHelpdesk::mdlActualizar($id, array(
            "estado" => "EN_PROGRESO",
            "cerrado_en" => null,
        ));
        if (!$ok) {
            return array("ok" => false, "msg" => "No se pudo reabrir el ticket.");
        }

        ModeloHelpdesk::mdlAgregarComentario(array(
            "ticket_id" => $id,
            "usuario_id" => $uid,
            "tipo_evento" => "REAPERTURA_USUARIO",
            "mensaje" => $mensaje,
            "estado_anterior" => "CERRADO",
            "estado_nuevo" => "EN_PROGRESO",
        ));

        return array(
            "ok" => true,
            "msg" => "Ticket reabierto. El equipo de TI lo verá de nuevo en la bandeja.",
        );
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
        if (!self::puedeVerTicket($ticket)) {
            return array("ok" => false, "msg" => "Sin acceso a este ticket.");
        }

        // Cerrado: solo el usuario autorizado puede reabrir (cambiar a otro estado)
        if ($ticket["estado"] === "CERRADO") {
            if (!self::ctrPuedeReabrir()) {
                return array(
                    "ok" => false,
                    "msg" => "Ticket cerrado. Solo el responsable autorizado puede reabrirlo.",
                );
            }
            $estadoPost = isset($_POST["estado"]) ? trim((string) $_POST["estado"]) : "";
            if ($estadoPost === "" || $estadoPost === "CERRADO" || !in_array($estadoPost, self::ESTADOS, true)) {
                return array(
                    "ok" => false,
                    "msg" => "Ticket cerrado. Cambie el estado a otro valor para reabrirlo.",
                );
            }
            $campos = array(
                "estado" => $estadoPost,
                "cerrado_en" => null,
            );
            if (!ModeloHelpdesk::mdlActualizar($id, $campos)) {
                return array("ok" => false, "msg" => "No se pudo reabrir el ticket.");
            }
            ModeloHelpdesk::mdlAgregarComentario(array(
                "ticket_id" => $id,
                "usuario_id" => self::ctrUsuarioSesionId(),
                "tipo_evento" => "CAMBIO_ESTADO",
                "mensaje" => "Estado: CERRADO → " . $estadoPost,
                "estado_anterior" => "CERRADO",
                "estado_nuevo" => $estadoPost,
            ));
            return array("ok" => true, "msg" => "Ticket reabierto.");
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

        if (isset($_POST["solicitante_id"]) && $_POST["solicitante_id"] !== "") {
            $nuevoSol = (int) $_POST["solicitante_id"];
            $actualSol = (int) $ticket["solicitante_id"];
            if ($nuevoSol < 1) {
                return array("ok" => false, "msg" => "Solicitante inválido.");
            }
            if ($nuevoSol !== $actualSol) {
                $usuariosSol = ModeloHelpdesk::mdlUsuariosPorIds(array($nuevoSol, $actualSol));
                $mapaSol = array();
                foreach ($usuariosSol as $uSol) {
                    $mapaSol[(int) $uSol["id"]] = $uSol["nombre"];
                }
                if (!isset($mapaSol[$nuevoSol])) {
                    return array("ok" => false, "msg" => "Solicitante no encontrado o inactivo.");
                }
                $nombreAnt = isset($mapaSol[$actualSol])
                    ? $mapaSol[$actualSol]
                    : (isset($ticket["solicitante_nombre"]) && $ticket["solicitante_nombre"] !== ""
                        ? $ticket["solicitante_nombre"]
                        : ("#" . $actualSol));
                $nombreNuevo = $mapaSol[$nuevoSol];
                $campos["solicitante_id"] = $nuevoSol;
                $eventos[] = array(
                    "tipo_evento" => "COMENTARIO",
                    "mensaje" => "Solicitante: " . $nombreAnt . " → " . $nombreNuevo,
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
                $eventos[] = array(
                    "tipo_evento" => "COMENTARIO",
                    "mensaje" => "Tipo: " . $ticket["tipo"] . " → " . $tipo,
                    "estado_anterior" => null,
                    "estado_nuevo" => null,
                );
            }
        }

        $tipoFinal = isset($campos["tipo"]) ? $campos["tipo"] : $ticket["tipo"];
        if (array_key_exists("fecha_estimada", $_POST) || isset($campos["tipo"])) {
            $fechaRaw = array_key_exists("fecha_estimada", $_POST)
                ? $_POST["fecha_estimada"]
                : (isset($ticket["fecha_estimada"]) ? $ticket["fecha_estimada"] : "");
            if (!in_array($tipoFinal, self::TIPOS_FECHA_ESTIMADA, true)) {
                $fechaEstimada = null;
            } else {
                $fechaEstimada = self::normalizarFechaEstimada($fechaRaw, $tipoFinal);
                if ($fechaEstimada === false) {
                    return array("ok" => false, "msg" => "Fecha estimada inválida.");
                }
            }
            $actualFecha = !empty($ticket["fecha_estimada"])
                ? substr((string) $ticket["fecha_estimada"], 0, 10)
                : null;
            if ($fechaEstimada !== $actualFecha) {
                $campos["fecha_estimada"] = $fechaEstimada;
                $eventos[] = array(
                    "tipo_evento" => "COMENTARIO",
                    "mensaje" => $fechaEstimada
                        ? ("Fecha estimada: " . ($actualFecha ? $actualFecha . " → " : "") . $fechaEstimada)
                        : "Fecha estimada eliminada",
                    "estado_anterior" => null,
                    "estado_nuevo" => null,
                );
            }
        }

        if (array_key_exists("sla_exento", $_POST)) {
            $quiereExento = !empty($_POST["sla_exento"]) && (string) $_POST["sla_exento"] !== "0";
            $actualExento = !empty($ticket["sla_exento"]) && (int) $ticket["sla_exento"] === 1;
            $motivo = isset($_POST["sla_exento_motivo"])
                ? trim((string) $_POST["sla_exento_motivo"])
                : "";

            if ($quiereExento && !$actualExento) {
                if ($motivo === "" || strlen($motivo) < 5) {
                    return array(
                        "ok" => false,
                        "msg" => "Para cancelar el SLA indique un motivo (mín. 5 caracteres).",
                    );
                }
                if (strlen($motivo) > 255) {
                    $motivo = substr($motivo, 0, 255);
                }
                $campos["sla_exento"] = 1;
                $campos["sla_exento_motivo"] = $motivo;
                $campos["sla_exento_en"] = date("Y-m-d H:i:s");
                $campos["sla_exento_por"] = $uid;
                $eventos[] = array(
                    "tipo_evento" => "COMENTARIO",
                    "mensaje" => "SLA cancelado. Motivo: " . $motivo,
                    "estado_anterior" => null,
                    "estado_nuevo" => null,
                );
            } elseif (!$quiereExento && $actualExento) {
                $campos["sla_exento"] = 0;
                $campos["sla_exento_motivo"] = null;
                $campos["sla_exento_en"] = null;
                $campos["sla_exento_por"] = null;
                $eventos[] = array(
                    "tipo_evento" => "COMENTARIO",
                    "mensaje" => "SLA reactivado.",
                    "estado_anterior" => null,
                    "estado_nuevo" => null,
                );
            } elseif ($quiereExento && $actualExento && $motivo !== "") {
                $motivoActual = isset($ticket["sla_exento_motivo"])
                    ? trim((string) $ticket["sla_exento_motivo"])
                    : "";
                if ($motivo !== $motivoActual) {
                    if (strlen($motivo) < 5) {
                        return array(
                            "ok" => false,
                            "msg" => "Motivo de SLA cancelado inválido (mín. 5 caracteres).",
                        );
                    }
                    if (strlen($motivo) > 255) {
                        $motivo = substr($motivo, 0, 255);
                    }
                    $campos["sla_exento_motivo"] = $motivo;
                    $eventos[] = array(
                        "tipo_evento" => "COMENTARIO",
                        "mensaje" => "Motivo SLA cancelado actualizado: " . $motivo,
                        "estado_anterior" => null,
                        "estado_nuevo" => null,
                    );
                }
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

    /**
     * Eliminación definitiva: solo USUARIO_ELIMINAR (id 6).
     * Borra comentarios, adjuntos (BD + archivos) y el ticket.
     */
    public static function ctrEliminar()
    {
        if (!self::ctrPuedeEliminar()) {
            return array("ok" => false, "msg" => "Sin permiso para eliminar tickets.");
        }

        $id = isset($_POST["id"]) ? (int) $_POST["id"] : 0;
        if ($id < 1) {
            return array("ok" => false, "msg" => "Ticket inválido.");
        }

        $ticket = ModeloHelpdesk::mdlObtener($id);
        if (!$ticket) {
            return array("ok" => false, "msg" => "Ticket no encontrado.");
        }

        $adjuntos = ModeloHelpdesk::mdlListarAdjuntos($id);
        if (!ModeloHelpdesk::mdlEliminarTicket($id)) {
            return array("ok" => false, "msg" => "No se pudo eliminar el ticket.");
        }

        foreach ($adjuntos as $adj) {
            if (empty($adj["nombre_guardado"])) {
                continue;
            }
            $path = self::pathAdjuntoAbs($adj["nombre_guardado"]);
            if (is_file($path)) {
                @unlink($path);
            }
        }

        return array("ok" => true, "msg" => "Ticket #" . $id . " eliminado.");
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
