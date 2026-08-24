<?php

require_once __DIR__ . "/permisos-modulos.config.php";

/**
 * Cuadre de ventas del día.
 * Paso 7: Procesar escribe en cuenta_ctejf (movimientos '-') y consume el abono.
 * Idempotente. Quien procesa no es quien validó (salvo ID de prueba).
 */
class ControladorCuadreVentas
{
    const SECTOR = "gestion_comercial";
    const MODULO = "cuadre_ventas";
    /** Solo pruebas: este ID ve todas las ventas del día, no solo las suyas. */
    const ID_PRUEBA_VER_TODAS = 6;

    /**
     * Medios del cuadre (cod_pago de tipo_pagosjf).
     * La OP es un dato aparte: si viene, se busca en Abonos; si no está, igual se registra.
     */
    public static function ctrCatalogoMedios()
    {
        return array(
            "80" => array("cod_pago" => "80", "label" => "Efectivo", "pide_op" => false),
            "15" => array("cod_pago" => "15", "label" => "Yape", "pide_op" => true),
            "05" => array("cod_pago" => "05", "label" => "Depósito", "pide_op" => true),
            "17" => array("cod_pago" => "17", "label" => "Tarjeta", "pide_op" => true),
            "16" => array("cod_pago" => "16", "label" => "Link de pago", "pide_op" => true),
            "14" => array("cod_pago" => "14", "label" => "Culqi", "pide_op" => true),
        );
    }

    public static function ctrNormalizarCodPago($tipo)
    {
        $tipo = strtoupper(trim((string) $tipo));
        $legado = array(
            "EFECTIVO" => "80",
            "YAPE" => "15",
            "ABONO_OP" => "05",
            "DEPOSITO" => "05",
            "TARJETA" => "17",
            "LINK" => "16",
            "CULQI" => "14",
            "CULQUI" => "14",
        );
        if (isset($legado[$tipo])) {
            return $legado[$tipo];
        }
        $cat = self::ctrCatalogoMedios();
        if (isset($cat[$tipo])) {
            return $tipo;
        }
        return "";
    }

    public static function ctrPuede($accion = "ver")
    {
        return function_exists("usuarioPuedeModulo")
            && usuarioPuedeModulo(self::SECTOR, self::MODULO, $accion);
    }

    public static function ctrUsuarioSesionId()
    {
        if (!isset($_SESSION["id"])) {
            return 0;
        }

        return (int) $_SESSION["id"];
    }

    public static function ctrPermisos()
    {
        return array(
            "ver" => self::ctrPuede("ver"),
            "registrar" => self::ctrPuede("registrar"),
            "validar" => self::ctrPuede("validar"),
            "procesar" => self::ctrPuede("procesar"),
        );
    }

    /**
     * Las ventas del cuadre son las que este usuario registró
     * (cuenta_ctejf.usuario = id de sesión). Nunca se toma de la petición.
     */
    public static function ctrUsuarioVentasFiltro()
    {
        return (string) self::ctrUsuarioSesionId();
    }

    public static function ctrVeTodasLasVentas()
    {
        return self::ctrUsuarioSesionId() === self::ID_PRUEBA_VER_TODAS;
    }

    public static function ctrNormalizarFecha($fecha)
    {
        $fecha = trim((string) $fecha);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return "";
        }
        $partes = explode("-", $fecha);
        if (!checkdate((int) $partes[1], (int) $partes[2], (int) $partes[0])) {
            return "";
        }
        return $fecha;
    }

    public static function ctrFilasExcel($fecha)
    {
        if (!self::ctrPuede("validar") && !self::ctrPuede("ver")) {
            return array("ok" => false, "msg" => "Sin permiso.");
        }

        $fechaOk = self::ctrNormalizarFecha($fecha);
        if ($fechaOk === "") {
            return array("ok" => false, "msg" => "Fecha no válida.");
        }

        $tipos = array(
            "01" => "Factura",
            "03" => "Boleta",
        );
        $catalogo = self::ctrCatalogoMedios();
        $brutas = ModeloCuadreVentas::mdlFilasExcelFecha($fechaOk);
        $mediosAll = ModeloCuadreVentas::mdlMediosExcelFecha($fechaOk);
        $mediosPorLote = array();
        foreach ($mediosAll as $m) {
            $idL = (int) $m["id_cuadre"];
            if (!isset($mediosPorLote[$idL])) {
                $mediosPorLote[$idL] = array();
            }
            $mediosPorLote[$idL][] = $m;
        }

        $docsPorLote = array();
        foreach ($brutas as $f) {
            $idL = (int) $f["id_cuadre"];
            if (!isset($docsPorLote[$idL])) {
                $docsPorLote[$idL] = array();
            }
            $docsPorLote[$idL][] = $f;
        }

        $pagoPorDoc = array();
        foreach ($docsPorLote as $idL => $docsLote) {
            $mediosLote = isset($mediosPorLote[$idL]) ? $mediosPorLote[$idL] : array();
            $cortes = array();
            if (!empty($docsLote) && !empty($mediosLote)) {
                try {
                    $cortes = ModeloCuadreVentas::mdlCortesPagosDocs($docsLote, $mediosLote);
                } catch (Exception $e) {
                    $cortes = array();
                }
            }
            foreach ($cortes as $corte) {
                $doc = $corte["doc"];
                $clave = $idL . "|" . trim((string) $doc["tipo_doc"]) . "|" . trim((string) $doc["num_cta"]);
                if (!isset($pagoPorDoc[$clave])) {
                    $pagoPorDoc[$clave] = array("ops" => array(), "formas" => array());
                }
                $ope = isset($corte["num_ope"]) ? trim((string) $corte["num_ope"]) : "";
                if ($ope !== "" && !in_array($ope, $pagoPorDoc[$clave]["ops"], true)) {
                    $pagoPorDoc[$clave]["ops"][] = $ope;
                }
                $codN = self::ctrNormalizarCodPago(isset($corte["cod_pago"]) ? $corte["cod_pago"] : "");
                $forma = ($codN !== "" && isset($catalogo[$codN])) ? $catalogo[$codN]["label"] : $codN;
                if ($forma !== "" && !in_array($forma, $pagoPorDoc[$clave]["formas"], true)) {
                    $pagoPorDoc[$clave]["formas"][] = $forma;
                }
            }
        }

        $filas = array();
        foreach ($brutas as $f) {
            $tipo = trim((string) $f["tipo_doc"]);
            $numCta = isset($f["num_cta"]) ? trim((string) $f["num_cta"]) : "";
            $idL = (int) $f["id_cuadre"];
            $clave = $idL . "|" . $tipo . "|" . $numCta;
            $dep = strtoupper(trim((string) $f["departamento"]));
            $zona = ($dep === "LIMA")
                ? trim((string) $f["distrito"])
                : trim((string) $f["departamento"]);
            $vend = trim((string) $f["vendedor"]);
            $vendNom = trim((string) $f["vendedor_nombre"]);
            $ops = array();
            $formas = array();
            if (isset($pagoPorDoc[$clave])) {
                $ops = $pagoPorDoc[$clave]["ops"];
                $formas = $pagoPorDoc[$clave]["formas"];
            }
            $emision = isset($f["fecha_emision"]) ? substr((string) $f["fecha_emision"], 0, 10) : "";
            $pago = isset($f["fecha_pago"]) ? substr((string) $f["fecha_pago"], 0, 10) : "";
            if ($pago === "") {
                $pago = $fechaOk;
            }
            if ($emision === "") {
                $emision = $fechaOk;
            }

            $estados = array(
                "REGISTRADO" => "Por validar",
                "VALIDADO" => "Confirmado",
                "PROCESADO" => "Procesado",
                "RECHAZADO" => "Rechazado",
                "ANULADO" => "Anulado",
            );
            $est = strtoupper(trim((string) (isset($f["estado"]) ? $f["estado"] : "")));

            $filas[] = array(
                "periodo" => substr($fechaOk, 5, 2) . "-" . substr($fechaOk, 0, 4),
                "fecha_dia" => $fechaOk,
                "responsable" => isset($f["responsable"]) ? trim((string) $f["responsable"]) : "",
                "marca" => ModeloCuadreVentas::mdlGrupoMarcaDeDocumento($numCta),
                "vendedor" => $vendNom !== "" ? ($vend . " — " . $vendNom) : $vend,
                "zona" => $zona,
                "codigo_cliente" => isset($f["cliente"]) ? trim((string) $f["cliente"]) : "",
                "documento_cliente" => isset($f["cliente_documento"]) ? trim((string) $f["cliente_documento"]) : "",
                "nombre_cliente" => isset($f["cliente_nombre"]) ? trim((string) $f["cliente_nombre"]) : "",
                "tipo_documento" => isset($tipos[$tipo]) ? $tipos[$tipo] : $tipo,
                "codigo_tipo" => $tipo,
                "nro_documento" => $numCta,
                "fecha_emision" => $emision,
                "monto" => isset($f["monto_doc"]) ? round((float) $f["monto_doc"], 2) : 0,
                "fecha_pago" => $pago,
                "monto_abonado" => isset($f["monto_aplicar"]) ? round((float) $f["monto_aplicar"], 2) : 0,
                "forma_pago" => implode(" | ", $formas),
                "nro_op" => implode("|", $ops),
                "estado" => isset($estados[$est]) ? $estados[$est] : $est,
            );
        }

        return array("ok" => true, "fecha" => $fechaOk, "filas" => $filas);
    }

    public static function ctrListarVentasDia($fecha)
    {
        if (!self::ctrPuede("ver")) {
            return array("ok" => false, "msg" => "Sin permiso.");
        }

        $fechaOk = self::ctrNormalizarFecha($fecha);
        if ($fechaOk === "") {
            return array("ok" => false, "msg" => "Fecha no válida.");
        }

        $usuario = self::ctrUsuarioVentasFiltro();
        if ($usuario === "0" || $usuario === "") {
            return array("ok" => false, "msg" => "Sesión no válida.");
        }

        $verTodas = self::ctrVeTodasLasVentas();
        $filas = ModeloCuadreVentas::mdlListarVentasDia($usuario, $fechaOk, $verTodas);
        $idsEnAbierto = ModeloCuadreVentas::mdlIdsCuentasEnLotes(array("BORRADOR", "REGISTRADO", "VALIDADO"));
        $borradores = ModeloCuadreVentas::mdlListarBorradoresDia(self::ctrUsuarioSesionId(), $fechaOk);
        $registrados = ModeloCuadreVentas::mdlListarBorradoresDia(self::ctrUsuarioSesionId(), $fechaOk, "REGISTRADO");
        $aplicarPorCuenta = array();
        $listaBorradores = array();
        foreach ($borradores as $b) {
            $idB = (int) $b["id"];
            $filasB = ModeloCuadreVentas::mdlDocsDeCuadre($idB);
            $docsB = array();
            foreach ($filasB as $db) {
                $idCta = (int) $db["id_cuenta"];
                $montoB = (float) $db["monto_aplicar"];
                $aplicarPorCuenta[$idCta] = $montoB;
                $docsB[] = array(
                    "id" => $idCta,
                    "monto_aplicar" => $montoB,
                );
            }
            $listaBorradores[] = array(
                "id" => $idB,
                "cliente" => isset($b["cliente"]) ? $b["cliente"] : "",
                "cliente_nombre" => isset($b["cliente_nombre"]) ? $b["cliente_nombre"] : "",
                "total_docs" => isset($b["total_docs"]) ? (float) $b["total_docs"] : 0,
                "n_docs" => isset($b["n_docs"]) ? (int) $b["n_docs"] : count($docsB),
                "docs" => $docsB,
            );
        }
        $borradorActivo = !empty($listaBorradores) ? $listaBorradores[0] : null;

        $listaRegistrados = array();
        foreach ($registrados as $r) {
            $listaRegistrados[] = array(
                "id" => (int) $r["id"],
                "cliente" => isset($r["cliente"]) ? $r["cliente"] : "",
                "cliente_nombre" => isset($r["cliente_nombre"]) ? $r["cliente_nombre"] : "",
                "total_docs" => isset($r["total_docs"]) ? (float) $r["total_docs"] : 0,
                "total_pagos" => isset($r["total_pagos"]) ? (float) $r["total_pagos"] : 0,
                "n_docs" => isset($r["n_docs"]) ? (int) $r["n_docs"] : 0,
            );
        }

        $docs = array();
        $totalMonto = 0.0;
        $totalSaldo = 0.0;

        foreach ($filas as $fila) {
            $monto = isset($fila["monto"]) ? (float) $fila["monto"] : 0.0;
            $saldo = isset($fila["saldo"]) ? (float) $fila["saldo"] : 0.0;
            $idCuenta = isset($fila["id"]) ? (int) $fila["id"] : 0;
            $totalMonto += $monto;
            $totalSaldo += $saldo;
            $docs[] = array(
                "id" => $idCuenta,
                "tipo_doc" => isset($fila["tipo_doc"]) ? trim((string) $fila["tipo_doc"]) : "",
                "num_cta" => isset($fila["num_cta"]) ? trim((string) $fila["num_cta"]) : "",
                "cliente" => isset($fila["cliente"]) ? trim((string) $fila["cliente"]) : "",
                "cliente_nombre" => isset($fila["cliente_nombre"]) ? trim((string) $fila["cliente_nombre"]) : "",
                "usuario" => isset($fila["usuario"]) ? trim((string) $fila["usuario"]) : "",
                "usuario_nombre" => isset($fila["usuario_nombre"]) ? trim((string) $fila["usuario_nombre"]) : "",
                "vendedor" => isset($fila["vendedor"]) ? trim((string) $fila["vendedor"]) : "",
                "monto" => $monto,
                "saldo" => $saldo,
                "estado" => isset($fila["estado"]) ? trim((string) $fila["estado"]) : "",
                "monto_aplicar" => isset($aplicarPorCuenta[$idCuenta]) ? $aplicarPorCuenta[$idCuenta] : null,
                "en_borrador" => isset($aplicarPorCuenta[$idCuenta]),
                "bloqueado" => isset($idsEnAbierto[$idCuenta]) && !isset($aplicarPorCuenta[$idCuenta]),
            );
        }

        return array(
            "ok" => true,
            "fecha" => $fechaOk,
            "usuario_ventas" => $usuario,
            "ver_todas" => $verTodas,
            "puede_registrar" => self::ctrPuede("registrar"),
            "puede_validar" => self::ctrPuede("validar"),
            "puede_procesar" => self::ctrPuede("procesar"),
            "docs" => $docs,
            "borrador" => $borradorActivo,
            "borradores" => $listaBorradores,
            "registrados" => $listaRegistrados,
            "pendientes_validar" => self::ctrPuede("validar")
                ? self::ctrArmarLotesFecha(
                    $fechaOk,
                    array("REGISTRADO", "VALIDADO", "PROCESADO", "RECHAZADO", "ANULADO"),
                    true
                )
                : array(),
            "validados" => (self::ctrPuede("validar") || self::ctrPuede("procesar"))
                ? self::ctrArmarLotesFecha($fechaOk, array("VALIDADO", "PROCESADO"), true)
                : array(),
            "totales" => array(
                "cantidad" => count($docs),
                "monto" => round($totalMonto, 2),
                "saldo" => round($totalSaldo, 2),
            ),
        );
    }

    public static function ctrGuardarBorrador($fecha, $docsInput)
    {
        if (!self::ctrPuede("registrar")) {
            return array("ok" => false, "msg" => "Sin permiso para registrar.");
        }

        $fechaOk = self::ctrNormalizarFecha($fecha);
        if ($fechaOk === "") {
            return array("ok" => false, "msg" => "Fecha no válida.");
        }

        if (!is_array($docsInput) || empty($docsInput)) {
            return array("ok" => false, "msg" => "Marca al menos un documento.");
        }

        $pedidos = array();
        foreach ($docsInput as $item) {
            $id = isset($item["id"]) ? (int) $item["id"] : 0;
            $aplicar = isset($item["monto_aplicar"]) ? round((float) $item["monto_aplicar"], 2) : 0.0;
            if ($id < 1 || $aplicar <= 0) {
                return array("ok" => false, "msg" => "Hay un monto a aplicar inválido.");
            }
            $pedidos[$id] = $aplicar;
        }

        $cargos = ModeloCuadreVentas::mdlCargosPorIds(array_keys($pedidos));
        if (count($cargos) !== count($pedidos)) {
            return array("ok" => false, "msg" => "Hay documentos que ya no están pendientes.");
        }

        $cliente = "";
        $usuarioVentas = "";
        $docsGuardar = array();
        $total = 0.0;
        $verTodas = self::ctrVeTodasLasVentas();
        $usuarioSesion = self::ctrUsuarioVentasFiltro();

        foreach ($cargos as $cargo) {
            $id = (int) $cargo["id"];
            if (isset($cargo["fecha"]) && $cargo["fecha"] !== $fechaOk) {
                return array("ok" => false, "msg" => "Hay documentos de otra fecha.");
            }
            if (!$verTodas && trim((string) $cargo["usuario"]) !== $usuarioSesion) {
                return array("ok" => false, "msg" => "Solo puedes cuadrar tus ventas.");
            }
            $cli = trim((string) $cargo["cliente"]);
            $usr = trim((string) $cargo["usuario"]);
            if ($cliente === "") {
                $cliente = $cli;
                $usuarioVentas = $usr;
            }
            if ($cli !== $cliente) {
                return array("ok" => false, "msg" => "El lote es de un solo cliente.");
            }
            if ($usr !== $usuarioVentas) {
                return array("ok" => false, "msg" => "No mezcles ventas de otro usuario en el mismo lote.");
            }
            $saldo = round((float) $cargo["saldo"], 2);
            $aplicar = $pedidos[$id];
            if ($aplicar > $saldo) {
                return array("ok" => false, "msg" => "El monto a aplicar no puede ser mayor al saldo.");
            }
            $total += $aplicar;
            $docsGuardar[] = array(
                "id_cuenta" => $id,
                "tipo_doc" => trim((string) $cargo["tipo_doc"]),
                "num_cta" => trim((string) $cargo["num_cta"]),
                "cliente" => $cli,
                "monto_doc" => round((float) $cargo["monto"], 2),
                "monto_aplicar" => $aplicar,
            );
        }

        $usuarioRegistro = self::ctrUsuarioSesionId();
        $existente = ModeloCuadreVentas::mdlBuscarBorrador($usuarioRegistro, $fechaOk, $cliente);
        $idCuadre = $existente ? (int) $existente["id"] : 0;

        foreach ($docsGuardar as $doc) {
            $otro = ModeloCuadreVentas::mdlLoteAbiertoDeCuenta($doc["id_cuenta"], $idCuadre);
            if ($otro) {
                return array("ok" => false, "msg" => "El documento " . $doc["num_cta"] . " ya está en otro cuadre.");
            }
        }

        $idOk = ModeloCuadreVentas::mdlGuardarBorrador(
            array(
                "id" => $idCuadre,
                "fecha_ventas" => $fechaOk,
                "usuario_ventas" => $usuarioVentas,
                "cliente" => $cliente,
                "total_docs" => round($total, 2),
                "usuario_registro" => $usuarioRegistro,
            ),
            $docsGuardar
        );

        if ($idOk < 1) {
            return array("ok" => false, "msg" => "No se pudo guardar el borrador.");
        }

        return array(
            "ok" => true,
            "msg" => "Borrador guardado. Aún no entra a cuentas.",
            "id" => $idOk,
            "cliente" => $cliente,
            "total_docs" => round($total, 2),
            "n_docs" => count($docsGuardar),
        );
    }

    public static function ctrBuscarOp($numOpe)
    {
        if (!self::ctrPuede("registrar")) {
            return array("ok" => false, "msg" => "Sin permiso para registrar.");
        }

        $ope = trim((string) $numOpe);
        if ($ope === "") {
            return array("ok" => false, "msg" => "Ingresa el número de OP.");
        }

        $filas = ModeloCuadreVentas::mdlBuscarAbonosPorOpe($ope);
        if (empty($filas)) {
            return array(
                "ok" => true,
                "encontrado" => false,
                "abono" => null,
                "abonos" => array(),
                "msg" => "Esa OP no está en Abonos. Se puede registrar igual.",
            );
        }

        $libres = array();
        foreach ($filas as $fila) {
            $res = isset($fila["id_cuadre"]) ? (int) $fila["id_cuadre"] : 0;
            if ($res < 1) {
                $libres[] = self::ctrSerializarAbono($fila);
            }
        }

        if (empty($libres)) {
            return array("ok" => false, "msg" => "Esa OP ya está reservada en otro cuadre.");
        }

        if (count($libres) === 1) {
            return array(
                "ok" => true,
                "encontrado" => true,
                "abono" => $libres[0],
                "abonos" => $libres,
            );
        }

        return array(
            "ok" => true,
            "encontrado" => true,
            "multiple" => true,
            "abono" => null,
            "abonos" => $libres,
            "msg" => "Hay varias OP con esos dígitos. Elige una.",
        );
    }

    private static function ctrSerializarAbono($fila)
    {
        return array(
            "id" => (int) $fila["id"],
            "num_ope" => trim((string) $fila["num_ope"]),
            "monto" => round((float) $fila["monto"], 2),
            "fecha" => isset($fila["fecha"]) ? $fila["fecha"] : "",
            "descripcion" => isset($fila["descripcion"]) ? $fila["descripcion"] : "",
            "agencia" => isset($fila["agencia"]) ? $fila["agencia"] : "",
        );
    }

    public static function ctrRegistrarPagos($fecha, $docsInput, $pagosInput)
    {
        if (!self::ctrPuede("registrar")) {
            return array("ok" => false, "msg" => "Sin permiso para registrar.");
        }

        $save = self::ctrGuardarBorrador($fecha, $docsInput);
        if (empty($save["ok"])) {
            return $save;
        }

        $idCuadre = (int) $save["id"];
        $totalDocs = round((float) $save["total_docs"], 2);
        $medios = self::ctrPrepararMedios($pagosInput, $idCuadre);
        if (isset($medios["ok"]) && $medios["ok"] === false) {
            return $medios;
        }

        $totalPagos = 0.0;
        foreach ($medios as $med) {
            $totalPagos += (float) $med["monto"];
        }
        $totalPagos = round($totalPagos, 2);
        if (abs($totalPagos - $totalDocs) > 0.009) {
            $hayAbono = false;
            foreach ($medios as $med) {
                if (!empty($med["id_abono"])) {
                    $hayAbono = true;
                    break;
                }
            }
            $msg = $hayAbono
                ? "El total de los abonos no cuadra con las boletas. Boletas: "
                    . number_format($totalDocs, 2, ".", ",")
                    . ". Abonos/pagos: "
                    . number_format($totalPagos, 2, ".", ",")
                    . "."
                : "Los pagos deben cuadrar con lo aplicado a documentos.";
            return array("ok" => false, "msg" => $msg);
        }

        $ok = ModeloCuadreVentas::mdlRegistrarPagos($idCuadre, $totalPagos, $medios);
        if ($ok !== true) {
            $msg = is_string($ok) && $ok !== "" ? $ok : "No se pudo registrar.";
            if ($msg === "OP ya reservada") {
                $msg = "Esa OP ya está reservada en otro cuadre.";
            }
            return array("ok" => false, "msg" => $msg);
        }

        return array(
            "ok" => true,
            "msg" => "Cuadre registrado. Si la OP estaba en Abonos, quedó reservada. Aún no entra a cuentas.",
            "id" => $idCuadre,
            "cliente" => isset($save["cliente"]) ? $save["cliente"] : "",
            "total_docs" => $totalDocs,
            "total_pagos" => $totalPagos,
            "n_docs" => isset($save["n_docs"]) ? $save["n_docs"] : 0,
        );
    }

    private static function ctrPrepararMedios($pagosInput, $idCuadre)
    {
        if (!is_array($pagosInput) || empty($pagosInput)) {
            return array("ok" => false, "msg" => "Agrega al menos un pago.");
        }

        $catalogo = self::ctrCatalogoMedios();
        $medios = array();
        $abonosUsados = array();
        $opesUsadas = array();

        foreach ($pagosInput as $pago) {
            $cod = self::ctrNormalizarCodPago(
                isset($pago["tipo_medio"]) ? $pago["tipo_medio"] : (isset($pago["cod_pago"]) ? $pago["cod_pago"] : "")
            );
            if ($cod === "" || !isset($catalogo[$cod])) {
                return array("ok" => false, "msg" => "Medio de pago no válido.");
            }
            $monto = isset($pago["monto"]) ? round((float) $pago["monto"], 2) : 0.0;
            if ($monto <= 0) {
                return array("ok" => false, "msg" => "Hay un monto de pago inválido.");
            }

            $pideOp = !empty($catalogo[$cod]["pide_op"]);
            $ope = $pideOp && isset($pago["num_ope"]) ? trim((string) $pago["num_ope"]) : "";
            $idAbono = 0;
            $opeGuardar = null;

            if ($pideOp && $ope !== "") {
                if (isset($opesUsadas[$ope])) {
                    return array("ok" => false, "msg" => "La misma OP no se puede usar dos veces en el lote.");
                }
                $opesUsadas[$ope] = true;

                $abono = null;
                $idAbonoIn = isset($pago["id_abono"]) ? (int) $pago["id_abono"] : 0;
                if ($idAbonoIn > 0) {
                    $abono = ModeloCuadreVentas::mdlAbonoPorId($idAbonoIn);
                }
                if (!$abono) {
                    $filas = ModeloCuadreVentas::mdlBuscarAbonosPorOpe($ope);
                    $libres = array();
                    foreach ($filas as $fila) {
                        $res = isset($fila["id_cuadre"]) ? (int) $fila["id_cuadre"] : 0;
                        if ($res < 1 || $res === (int) $idCuadre) {
                            $libres[] = $fila;
                        }
                    }
                    if (count($libres) === 1) {
                        $abono = $libres[0];
                    } elseif (count($libres) > 1) {
                        return array(
                            "ok" => false,
                            "msg" => "Hay varias OP con esos dígitos. Elige una de la lista.",
                        );
                    } elseif (!empty($filas)) {
                        $otra = trim((string) $filas[0]["num_ope"]);
                        return array("ok" => false, "msg" => "La OP " . $otra . " ya está en otro cuadre.");
                    }
                }

                if ($abono) {
                    $res = isset($abono["id_cuadre"]) ? (int) $abono["id_cuadre"] : 0;
                    $ope = trim((string) $abono["num_ope"]);
                    if ($res > 0 && $res !== (int) $idCuadre) {
                        return array("ok" => false, "msg" => "La OP " . $ope . " ya está en otro cuadre.");
                    }
                    $disponible = round((float) $abono["monto"], 2);
                    if (abs($monto - $disponible) > 0.009) {
                        return array(
                            "ok" => false,
                            "msg" => "La OP " . $ope . " es de "
                                . number_format($disponible, 2, ".", ",")
                                . ". El abono se usa completo para que cuadre con las boletas.",
                        );
                    }
                    $idA = (int) $abono["id"];
                    if (isset($abonosUsados[$idA])) {
                        return array("ok" => false, "msg" => "La misma OP no se puede usar dos veces en el lote.");
                    }
                    $abonosUsados[$idA] = true;
                    $idAbono = $idA;
                }
                $opeGuardar = $ope;
            }

            $medios[] = array(
                "tipo_medio" => $cod,
                "id_abono" => $idAbono,
                "num_ope" => $opeGuardar,
                "monto" => $monto,
            );
        }

        return $medios;
    }

    public static function ctrValidarCuadre($idCuadre)
    {
        if (!self::ctrPuede("validar")) {
            return array("ok" => false, "msg" => "Sin permiso para validar.");
        }

        $chequeo = self::ctrLoteParaValidar($idCuadre);
        if (empty($chequeo["ok"])) {
            return $chequeo;
        }

        $ok = ModeloCuadreVentas::mdlValidarCuadre($idCuadre, self::ctrUsuarioSesionId());
        if (!$ok) {
            return array("ok" => false, "msg" => "El lote ya no está pendiente de validar.");
        }

        return array(
            "ok" => true,
            "msg" => "Cuadre confirmado. Aún no entra a cuentas.",
            "id" => (int) $idCuadre,
        );
    }

    public static function ctrRechazarCuadre($idCuadre, $motivo)
    {
        if (!self::ctrPuede("validar")) {
            return array("ok" => false, "msg" => "Sin permiso para validar.");
        }

        $motivo = trim((string) $motivo);
        if ($motivo === "") {
            return array("ok" => false, "msg" => "Indica el motivo del rechazo.");
        }
        if (function_exists("mb_substr")) {
            $motivo = mb_substr($motivo, 0, 500, "UTF-8");
        } else {
            $motivo = substr($motivo, 0, 500);
        }

        $chequeo = self::ctrLoteParaValidar($idCuadre);
        if (empty($chequeo["ok"])) {
            return $chequeo;
        }

        $ok = ModeloCuadreVentas::mdlRechazarCuadre($idCuadre, self::ctrUsuarioSesionId(), $motivo);
        if ($ok !== true) {
            $msg = is_string($ok) && $ok !== "" ? $ok : "No se pudo rechazar.";
            return array("ok" => false, "msg" => $msg);
        }

        return array(
            "ok" => true,
            "msg" => "Cuadre rechazado. La OP quedó libre otra vez.",
            "id" => (int) $idCuadre,
        );
    }

    public static function ctrAnularCuadre($idCuadre)
    {
        if (!self::ctrPuede("registrar")) {
            return array("ok" => false, "msg" => "Sin permiso para cancelar.");
        }

        $idCuadre = (int) $idCuadre;
        if ($idCuadre < 1) {
            return array("ok" => false, "msg" => "Lote no válido.");
        }

        $lote = ModeloCuadreVentas::mdlCuadrePorId($idCuadre);
        if (!$lote) {
            return array("ok" => false, "msg" => "No se encontró el cuadre.");
        }
        if (strtoupper(trim((string) $lote["estado"])) !== "REGISTRADO") {
            return array("ok" => false, "msg" => "Ese cuadre ya no se puede cancelar.");
        }
        if ((int) $lote["usuario_registro"] !== self::ctrUsuarioSesionId()) {
            return array("ok" => false, "msg" => "Solo puedes cancelar un cuadre que registraste tú.");
        }

        $ok = ModeloCuadreVentas::mdlAnularCuadre($idCuadre, "Anulado por quien lo registró");
        if ($ok !== true) {
            $msg = is_string($ok) && $ok !== "" ? $ok : "No se pudo cancelar.";
            return array("ok" => false, "msg" => $msg);
        }

        return array(
            "ok" => true,
            "msg" => "Cuadre cancelado. La OP quedó libre y puedes armarlo de nuevo.",
            "id" => $idCuadre,
        );
    }

    /**
     * Pasa un lote VALIDADO a cuenta corriente.
     * Quien validó no procesa (salvo ID de prueba). Si ya está PROCESADO, no repite.
     */
    public static function ctrProcesarCuadre($idCuadre)
    {
        if (!self::ctrPuede("procesar")) {
            return array("ok" => false, "msg" => "Sin permiso para procesar.");
        }

        $idCuadre = (int) $idCuadre;
        if ($idCuadre < 1) {
            return array("ok" => false, "msg" => "Lote no válido.");
        }

        $lote = ModeloCuadreVentas::mdlCuadrePorId($idCuadre);
        if (!$lote) {
            return array("ok" => false, "msg" => "No se encontró el cuadre.");
        }

        $estado = strtoupper(trim((string) $lote["estado"]));
        if ($estado === "PROCESADO") {
            return array(
                "ok" => true,
                "ya" => true,
                "msg" => "Este cuadre ya entró a cuentas.",
                "id" => $idCuadre,
            );
        }
        if ($estado !== "VALIDADO") {
            return array("ok" => false, "msg" => "Solo se procesan cuadres ya confirmados.");
        }

        $yo = self::ctrUsuarioSesionId();
        $usrVal = isset($lote["usuario_validacion"]) ? (int) $lote["usuario_validacion"] : 0;
        if ($usrVal > 0 && $usrVal === $yo && !self::ctrVeTodasLasVentas()) {
            return array("ok" => false, "msg" => "No puedes procesar un cuadre que validaste tú.");
        }

        $usureg = "";
        if (isset($_SESSION["nombre"])) {
            $usureg = (string) $_SESSION["nombre"];
        }
        $pcreg = "";
        if (isset($_SERVER["REMOTE_ADDR"])) {
            $pcreg = gethostbyaddr($_SERVER["REMOTE_ADDR"]);
        }

        $res = ModeloCuadreVentas::mdlProcesarACte(
            $idCuadre,
            array(
                "usureg" => $usureg,
                "pcreg" => $pcreg,
                "usuario_proceso" => $yo,
            )
        );
        if (empty($res["ok"])) {
            $msg = isset($res["msg"]) && $res["msg"] !== "" ? $res["msg"] : "No se pudo procesar.";
            return array("ok" => false, "msg" => $msg);
        }

        if (!empty($res["ya"])) {
            return array(
                "ok" => true,
                "ya" => true,
                "msg" => "Este cuadre ya entró a cuentas.",
                "id" => $idCuadre,
            );
        }

        return array(
            "ok" => true,
            "msg" => "Cuadre procesado. Ya entra a cuentas.",
            "id" => $idCuadre,
        );
    }

    private static function ctrLoteParaValidar($idCuadre)
    {
        $idCuadre = (int) $idCuadre;
        if ($idCuadre < 1) {
            return array("ok" => false, "msg" => "Lote no válido.");
        }

        $lote = ModeloCuadreVentas::mdlCuadrePorId($idCuadre);
        if (!$lote) {
            return array("ok" => false, "msg" => "No se encontró el cuadre.");
        }
        if (strtoupper(trim((string) $lote["estado"])) !== "REGISTRADO") {
            return array("ok" => false, "msg" => "Ese cuadre ya no está pendiente de validar.");
        }
        if ((int) $lote["usuario_registro"] === self::ctrUsuarioSesionId()
            && !self::ctrVeTodasLasVentas()) {
            return array("ok" => false, "msg" => "No puedes validar un cuadre que registraste tú.");
        }

        return array("ok" => true, "lote" => $lote);
    }

    private static function ctrArmarLotesFecha($fecha, $estados, $conDetalle)
    {
        $filas = ModeloCuadreVentas::mdlListarLotesFecha($fecha, $estados);
        $out = array();
        $yo = self::ctrUsuarioSesionId();
        foreach ($filas as $fila) {
            $id = (int) $fila["id"];
            $usrReg = isset($fila["usuario_registro"]) ? (int) $fila["usuario_registro"] : 0;
            $item = array(
                "id" => $id,
                "cliente" => isset($fila["cliente"]) ? $fila["cliente"] : "",
                "cliente_nombre" => isset($fila["cliente_nombre"]) ? $fila["cliente_nombre"] : "",
                "usuario_registro" => $usrReg,
                "usuario_registro_nombre" => isset($fila["usuario_registro_nombre"]) ? $fila["usuario_registro_nombre"] : "",
                "usuario_ventas" => isset($fila["usuario_ventas"]) ? $fila["usuario_ventas"] : "",
                "total_docs" => isset($fila["total_docs"]) ? (float) $fila["total_docs"] : 0,
                "total_pagos" => isset($fila["total_pagos"]) ? (float) $fila["total_pagos"] : 0,
                "n_docs" => isset($fila["n_docs"]) ? (int) $fila["n_docs"] : 0,
                "fecha_registro" => isset($fila["fecha_registro"]) ? $fila["fecha_registro"] : "",
                "estado" => isset($fila["estado"]) ? strtoupper(trim((string) $fila["estado"])) : "",
                "observacion" => isset($fila["observacion"]) ? trim((string) $fila["observacion"]) : "",
                "es_propio" => $usrReg === $yo,
            );
            if ($conDetalle) {
                $item["docs"] = self::ctrSerializarDocs(ModeloCuadreVentas::mdlDocsDeCuadre($id));
                $item["medios"] = self::ctrSerializarMedios(ModeloCuadreVentas::mdlMediosDeCuadre($id));
            }
            $out[] = $item;
        }
        return $out;
    }

    private static function ctrSerializarDocs($filas)
    {
        $out = array();
        foreach ($filas as $d) {
            $out[] = array(
                "id_cuenta" => isset($d["id_cuenta"]) ? (int) $d["id_cuenta"] : 0,
                "tipo_doc" => isset($d["tipo_doc"]) ? trim((string) $d["tipo_doc"]) : "",
                "num_cta" => isset($d["num_cta"]) ? trim((string) $d["num_cta"]) : "",
                "cliente" => isset($d["cliente"]) ? trim((string) $d["cliente"]) : "",
                "monto_doc" => isset($d["monto_doc"]) ? (float) $d["monto_doc"] : 0,
                "monto_aplicar" => isset($d["monto_aplicar"]) ? (float) $d["monto_aplicar"] : 0,
            );
        }
        return $out;
    }

    private static function ctrSerializarMedios($filas)
    {
        $out = array();
        foreach ($filas as $m) {
            $cod = self::ctrNormalizarCodPago(isset($m["tipo_medio"]) ? $m["tipo_medio"] : "");
            $out[] = array(
                "tipo_medio" => $cod !== "" ? $cod : (isset($m["tipo_medio"]) ? $m["tipo_medio"] : ""),
                "cod_pago" => $cod,
                "id_abono" => isset($m["id_abono"]) ? (int) $m["id_abono"] : 0,
                "num_ope" => isset($m["num_ope"]) ? $m["num_ope"] : "",
                "monto" => isset($m["monto"]) ? (float) $m["monto"] : 0,
            );
        }
        return $out;
    }
}
