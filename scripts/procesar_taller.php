<?php

/**
 * Script para procesar inventario físico de Taller
 * 
 * Este script:
 * 1. Lee un CSV con artículos y cantidades físicas en taller
 * 2. Crea Orden de Corte OBLIGATORIAMENTE (grupo independiente)
 * 3. Crea Almacén de Corte OBLIGATORIAMENTE (grupo independiente)
 * 4. Registra en entaller_cabjf vinculado a almacencorte_detallejf
 * 5. Actualiza stocks (taller y alm_corte)
 * 
 * Formato CSV requerido:
 * articulo,cantidad
 * ART001,50
 * ART002,30
 */

// Incluir conexión y modelos
require_once __DIR__ . '/../modelos/conexion.php';
require_once __DIR__ . '/../modelos/ordencorte.modelo.php';
require_once __DIR__ . '/../modelos/almacencorte.modelo.php';
require_once __DIR__ . '/../modelos/articulos.modelo.php';
require_once __DIR__ . '/../modelos/cortes.modelo.php';

// Configuración
define('CSV_PATH', __DIR__ . '/csv/taller.csv');
define('USUARIO_ID', 6);
define('CONFIGURACION_DEFAULT', 'INV-TALLER-' . date('Ymd'));
// Legado: import CSV sigue escribiendo VC (compat lectura con sectorjf internos).
// No migrar a un T* concreto sin definir taller default del lote.
define('TALLER_INTERNO', 'VC');

// Colores para output
define('COLOR_RESET', "\033[0m");
define('COLOR_SUCCESS', "\033[32m");
define('COLOR_ERROR', "\033[31m");
define('COLOR_INFO', "\033[36m");
define('COLOR_WARNING', "\033[33m");

/**
 * Función para mostrar mensajes con colores
 */
function mensaje($texto, $tipo = 'info')
{
    $color = COLOR_INFO;
    switch ($tipo) {
        case 'success':
            $color = COLOR_SUCCESS;
            break;
        case 'error':
            $color = COLOR_ERROR;
            break;
        case 'warning':
            $color = COLOR_WARNING;
            break;
    }
    echo $color . $texto . COLOR_RESET . "\n";
}

/**
 * Leer CSV y retornar array de artículos con taller
 */
function leerCSV($archivo)
{
    if (!file_exists($archivo)) {
        throw new Exception("El archivo CSV no existe: $archivo");
    }

    $articulos = [];
    $handle = fopen($archivo, 'r');

    if ($handle === false) {
        throw new Exception("No se pudo abrir el archivo CSV");
    }

    // Leer encabezados (primera línea)
    $encabezados = fgetcsv($handle);

    // Validar encabezados
    if (!$encabezados || strtolower($encabezados[0]) !== 'articulo') {
        throw new Exception("El CSV debe tener encabezados: articulo,cantidad");
    }

    $linea = 1;
    while (($fila = fgetcsv($handle)) !== false) {
        $linea++;

        if (count($fila) < 2) {
            mensaje("Advertencia: Línea $linea ignorada (formato incorrecto)", 'warning');
            continue;
        }

        $articulo = trim($fila[0]);
        $cantidad = intval(trim($fila[1]));

        if (empty($articulo) || $cantidad <= 0) {
            mensaje("Advertencia: Línea $linea ignorada (artículo vacío o cantidad inválida)", 'warning');
            continue;
        }

        // Agrupar por artículo (sumar cantidades si se repite)
        if (isset($articulos[$articulo])) {
            $articulos[$articulo]['cantidad'] += $cantidad;
            mensaje("Artículo $articulo: cantidad sumada (total: {$articulos[$articulo]['cantidad']})", 'info');
        } else {
            $articulos[$articulo] = [
                'articulo' => $articulo,
                'cantidad' => $cantidad,
                'taller' => TALLER_INTERNO // Siempre el mismo taller interno
            ];
        }
    }

    fclose($handle);

    return $articulos;
}

/**
 * Obtener último código de orden de corte
 */
function obtenerUltimoCodigoOC()
{
    $resultado = ModeloOrdenCorte::mdlUltimoId();
    if ($resultado && isset($resultado[0]['ult_codigo'])) {
        return intval($resultado[0]['ult_codigo']);
    }
    return 0;
}

/**
 * Obtener último código de almacén de corte
 */
function obtenerUltimoCodigoAC()
{
    $resultado = ModeloAlmacenCorte::mdlUltimoCodigoAC();
    if ($resultado && isset($resultado['ultimo_codigo'])) {
        return intval($resultado['ultimo_codigo']);
    }
    return 0;
}

/**
 * Generar número de guía
 */
function generarGuia()
{
    return 'GUIA-TALLER-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
}

/**
 * Crear orden de corte
 */
function crearOrdenCorte($codigo, $articulos, $usuario, $configuracion)
{
    $pdo = Conexion::conectar();
    $pdo->beginTransaction();

    try {
        // Calcular total (sumar todas las cantidades)
        $total = 0;
        foreach ($articulos as $item) {
            $total += $item['cantidad'];
        }

        // Crear cabecera de orden de corte
        $datosOC = [
            'codigo' => $codigo,
            'usuario' => $usuario,
            'total' => $total,
            'saldo' => $total,
            'configuracion' => $configuracion,
            'estado' => 'Pendiente'
        ];

        $resultado = ModeloOrdenCorte::mdlGuardarOrdenCorte('ordencortejf', $datosOC);

        if ($resultado !== 'ok') {
            throw new Exception("Error al crear orden de corte");
        }

        // Agrupar artículos únicos (sumar cantidades si se repite)
        $articulosUnicos = [];
        foreach ($articulos as $item) {
            $art = trim($item['articulo']);
            if (isset($articulosUnicos[$art])) {
                $articulosUnicos[$art] += $item['cantidad'];
            } else {
                $articulosUnicos[$art] = $item['cantidad'];
            }
        }

        // Crear detalles de orden de corte
        // Usar consulta directa para asegurar que articulo se guarde como STRING
        foreach ($articulosUnicos as $articulo => $cantidad) {
            $artLimpio = trim($articulo);

            // Insertar directamente usando PARAM_STR para el artículo
            $stmt = $pdo->prepare("INSERT INTO detalles_ordencortejf (ordencorte, articulo, cantidad, saldo) 
                VALUES (:ordencorte, :articulo, :cantidad, :saldo)");

            $stmt->bindParam(":ordencorte", $codigo, PDO::PARAM_INT);
            $stmt->bindParam(":articulo", $artLimpio, PDO::PARAM_STR); // IMPORTANTE: STR no INT
            $stmt->bindParam(":cantidad", $cantidad, PDO::PARAM_INT);
            $stmt->bindParam(":saldo", $cantidad, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                throw new Exception("Error al crear detalle de orden de corte para artículo $artLimpio");
            }

            // Actualizar ord_corte en articulojf
            ModeloArticulos::mdlSumarOrdCorte($cantidad, $artLimpio);
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Crear almacén de corte
 */
function crearAlmacenCorte($codigoAC, $codigoOC, $guia, $articulos, $usuario)
{
    $pdo = Conexion::conectar();
    $pdo->beginTransaction();

    try {
        // Calcular total (sumar todas las cantidades)
        $total = 0;
        foreach ($articulos as $item) {
            $total += $item['cantidad'];
        }

        // Crear cabecera de almacén de corte
        $datosAC = [
            'codigo' => $codigoAC,
            'guia' => $guia,
            'usuario' => $usuario,
            'total' => $total,
            'estado' => '1' // Procesado
        ];

        $resultado = ModeloAlmacenCorte::mdlGuardarAlmacenCorte($datosAC);

        if ($resultado !== 'ok') {
            throw new Exception("Error al crear almacén de corte");
        }

        // Obtener IDs de detalles de orden de corte para vincular
        $stmt = $pdo->prepare("SELECT id, articulo FROM detalles_ordencortejf WHERE ordencorte = :ordencorte");
        $stmt->bindParam(":ordencorte", $codigoOC, PDO::PARAM_INT);
        $stmt->execute();
        $detallesOC = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$detallesOC) {
            throw new Exception("No se encontraron detalles de orden de corte");
        }

        // Crear mapa de artículo -> id detalle OC
        $mapaDetalles = [];
        foreach ($detallesOC as $detalle) {
            $art = trim($detalle['articulo']);
            $mapaDetalles[$art] = $detalle['id'];
        }

        // Ya tenemos artículos únicos del paso anterior, pero necesitamos el formato correcto
        $articulosUnicos = [];
        foreach ($articulos as $item) {
            $art = trim($item['articulo']);
            if (isset($articulosUnicos[$art])) {
                $articulosUnicos[$art] += $item['cantidad'];
            } else {
                $articulosUnicos[$art] = $item['cantidad'];
            }
        }

        // Crear detalles de almacén de corte
        foreach ($articulosUnicos as $articulo => $cantidad) {
            $artLimpio = trim($articulo);

            if (!isset($mapaDetalles[$artLimpio])) {
                throw new Exception("No se encontró detalle de orden de corte para artículo $artLimpio");
            }

            // Insertar directamente usando PARAM_STR para el artículo
            // Nota: El campo se llama 'ordencorte' y 'detordencorte' en la tabla
            $stmt = $pdo->prepare("INSERT INTO almacencorte_detallejf 
                (almacencorte, ordencorte, detordencorte, articulo, cantidad) 
                VALUES (:almacencorte, :ordencorte, :detordencorte, :articulo, :cantidad)");

            $stmt->bindParam(":almacencorte", $codigoAC, PDO::PARAM_INT);
            $stmt->bindParam(":ordencorte", $codigoOC, PDO::PARAM_INT);
            $stmt->bindParam(":detordencorte", $mapaDetalles[$artLimpio], PDO::PARAM_INT);
            $stmt->bindParam(":articulo", $artLimpio, PDO::PARAM_STR); // IMPORTANTE: STR no INT
            $stmt->bindParam(":cantidad", $cantidad, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                throw new Exception("Error al crear detalle de almacén de corte para artículo $artLimpio");
            }

            // Obtener el ID del detalle recién creado e inicializar saldo_taller
            $stmt = $pdo->prepare("SELECT id FROM almacencorte_detallejf 
                WHERE almacencorte = :almacencorte 
                AND articulo = :articulo 
                AND ordencorte = :ordencorte
                ORDER BY id DESC LIMIT 1");
            $stmt->bindParam(":almacencorte", $codigoAC, PDO::PARAM_INT);
            $stmt->bindParam(":articulo", $artLimpio, PDO::PARAM_STR);
            $stmt->bindParam(":ordencorte", $codigoOC, PDO::PARAM_INT);
            $stmt->execute();
            $detalleCreado = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if ($detalleCreado) {
                // Inicializar saldo_taller con la cantidad (ya que acabamos de crear el almacén de corte)
                $stmt = $pdo->prepare("UPDATE almacencorte_detallejf 
                    SET saldo_taller = :cantidad 
                    WHERE id = :id");
                $stmt->bindParam(":cantidad", $cantidad, PDO::PARAM_INT);
                $stmt->bindParam(":id", $detalleCreado['id'], PDO::PARAM_INT);
                $stmt->execute();
                $stmt->closeCursor();
            }

            // Actualizar stocks en articulojf
            // Sumar a alm_corte
            ModeloAlmacenCorte::mdlActualizarAlmCorte($artLimpio, $cantidad);

            // Restar de ord_corte
            ModeloAlmacenCorte::mdlActualizarOrdCorte($artLimpio, $cantidad);

            // Actualizar saldo en detalle de orden de corte
            ModeloAlmacenCorte::mdlActualizarSaldoOrdCorte($artLimpio, $codigoOC, $cantidad);
        }

        // Actualizar saldos generales de orden de corte
        ModeloAlmacenCorte::mdlActualizarOrdCorteSaldo();
        ModeloAlmacenCorte::mdlActualizarSaldoOrdCorteGral();
        ModeloAlmacenCorte::mdlActualizarOrdCorteEstadoParcial();
        ModeloAlmacenCorte::mdlActualizarOrdCorteEstadoCerrado();

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Registrar artículos en taller (entaller_cabjf)
 */
function registrarEnTaller($articulos, $codigoAC, $usuario)
{
    $pdo = Conexion::conectar();
    $pdo->beginTransaction();

    try {
        $guia = generarGuia();
        $registrosCreados = 0;

        foreach ($articulos as $item) {
            $articulo = trim($item['articulo']);
            $cantidad = $item['cantidad'];

            // Buscar el detalle de almacén de corte que acabamos de crear
            // Como acabamos de crear el almacén de corte, el saldo_taller debería estar inicializado
            $stmt = $pdo->prepare("SELECT acd.id, COALESCE(acd.saldo_taller, acd.cantidad) AS saldo_disponible, acd.cantidad
                FROM almacencorte_detallejf acd
                WHERE acd.articulo = :articulo
                  AND acd.almacencorte = :almacencorte
                  AND COALESCE(acd.saldo_taller, acd.cantidad) > 0
                ORDER BY acd.id ASC
                LIMIT 1");

            $stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);
            $stmt->bindParam(":almacencorte", $codigoAC, PDO::PARAM_INT);
            $stmt->execute();
            $detalle = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (!$detalle || $detalle['saldo_disponible'] <= 0) {
                // Si no encontramos el detalle, intentar usar la cantidad directamente
                // Esto puede pasar si saldo_taller no se inicializó correctamente
                $stmt = $pdo->prepare("SELECT acd.id, acd.cantidad AS saldo_disponible
                    FROM almacencorte_detallejf acd
                    WHERE acd.articulo = :articulo
                      AND acd.almacencorte = :almacencorte
                    ORDER BY acd.id ASC
                    LIMIT 1");
                $stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);
                $stmt->bindParam(":almacencorte", $codigoAC, PDO::PARAM_INT);
                $stmt->execute();
                $detalle = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                if (!$detalle) {
                    mensaje("Advertencia: No se encontró detalle de almacén de corte para artículo $articulo", 'warning');
                    continue;
                }

                // Inicializar saldo_taller si no estaba inicializado
                $stmt = $pdo->prepare("UPDATE almacencorte_detallejf 
                    SET saldo_taller = :cantidad 
                    WHERE id = :id");
                $stmt->bindParam(":cantidad", $detalle['saldo_disponible'], PDO::PARAM_INT);
                $stmt->bindParam(":id", $detalle['id'], PDO::PARAM_INT);
                $stmt->execute();
                $stmt->closeCursor();

                $detalle['saldo_disponible'] = $detalle['saldo_disponible'];
            }

            $detalleId = $detalle['id'];
            $saldoDisponible = $detalle['saldo_disponible'];
            $cantidadAUsar = min($saldoDisponible, $cantidad);

            // Insertar en entaller_cabjf
            $stmt = $pdo->prepare("INSERT INTO entaller_cabjf
                (articulo, usuario, cantidad, saldo, estado, guia, taller, almacencorte_detalle_id) 
                VALUES
                (:articulo, :usuario, :cantidad, :saldo, :estado, :guia, :taller, :almacencorte_detalle_id)");

            $estado = '0'; // Pendiente
            $tallerInterno = TALLER_INTERNO; // Asignar constante a variable para bindParam
            $stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);
            $stmt->bindParam(":usuario", $usuario, PDO::PARAM_INT);
            $stmt->bindParam(":cantidad", $cantidadAUsar, PDO::PARAM_INT);
            $stmt->bindParam(":saldo", $cantidadAUsar, PDO::PARAM_INT);
            $stmt->bindParam(":estado", $estado, PDO::PARAM_STR);
            $stmt->bindParam(":guia", $guia, PDO::PARAM_STR);
            $stmt->bindParam(":taller", $tallerInterno, PDO::PARAM_STR);
            $stmt->bindParam(":almacencorte_detalle_id", $detalleId, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                throw new Exception("Error al registrar artículo $articulo en taller");
            }

            // Actualizar saldo_taller del detalle
            $nuevoSaldo = max(0, $saldoDisponible - $cantidadAUsar);
            $stmt = $pdo->prepare("UPDATE almacencorte_detallejf
                SET saldo_taller = :nuevo_saldo
                WHERE id = :detalle_id");
            $stmt->bindParam(":nuevo_saldo", $nuevoSaldo, PDO::PARAM_INT);
            $stmt->bindParam(":detalle_id", $detalleId, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();

            // Actualizar articulojf: aumentar taller y disminuir alm_corte
            $stmt = $pdo->prepare("UPDATE articulojf 
                SET taller = taller + :cantidad,
                    alm_corte = GREATEST(alm_corte - :cantidad, 0)
                WHERE articulo = :articulo");
            $stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);
            $stmt->bindParam(":cantidad", $cantidadAUsar, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();

            $registrosCreados++;
        }

        $pdo->commit();
        return ['registros' => $registrosCreados, 'guia' => $guia];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Función principal
 */
function procesarInventario()
{
    try {
        mensaje("============================================", 'info');
        mensaje("PROCESADOR DE INVENTARIO - TALLER", 'info');
        mensaje("============================================", 'info');
        mensaje("");

        // Leer CSV
        mensaje("Leyendo archivo CSV...", 'info');
        $articulos = leerCSV(CSV_PATH);

        if (empty($articulos)) {
            throw new Exception("El CSV está vacío o no contiene datos válidos");
        }

        mensaje("Artículos encontrados: " . count($articulos), 'success');
        $totalUnidades = 0;
        foreach ($articulos as $item) {
            $totalUnidades += $item['cantidad'];
        }
        mensaje("Total de unidades: " . $totalUnidades, 'success');
        mensaje("");

        // Obtener códigos
        mensaje("Obteniendo códigos disponibles...", 'info');
        $ultimoOC = obtenerUltimoCodigoOC();
        $nuevoOC = $ultimoOC + 1;
        mensaje("Nueva Orden de Corte: $nuevoOC", 'info');

        $ultimoAC = obtenerUltimoCodigoAC();
        $nuevoAC = $ultimoAC + 1;
        mensaje("Nuevo Almacén de Corte: $nuevoAC", 'info');
        mensaje("");

        // Crear orden de corte
        mensaje("Creando Orden de Corte...", 'info');
        crearOrdenCorte($nuevoOC, $articulos, USUARIO_ID, CONFIGURACION_DEFAULT);
        mensaje("✓ Orden de Corte creada exitosamente", 'success');
        mensaje("");

        // Crear almacén de corte
        $guiaAC = generarGuia();
        mensaje("Creando Almacén de Corte...", 'info');
        crearAlmacenCorte($nuevoAC, $nuevoOC, $guiaAC, $articulos, USUARIO_ID);
        mensaje("✓ Almacén de Corte creado exitosamente", 'success');
        mensaje("");

        // Registrar en taller
        mensaje("Registrando artículos en Taller...", 'info');
        $resultadoTaller = registrarEnTaller($articulos, $nuevoAC, USUARIO_ID);
        mensaje("✓ Registrados {$resultadoTaller['registros']} artículos en taller", 'success');
        mensaje("  Guía de taller: {$resultadoTaller['guia']}", 'info');
        mensaje("");

        mensaje("============================================", 'success');
        mensaje("PROCESO COMPLETADO EXITOSAMENTE", 'success');
        mensaje("============================================", 'success');
        mensaje("");
        mensaje("Resumen:", 'info');
        mensaje("  - Orden de Corte: $nuevoOC", 'info');
        mensaje("  - Almacén de Corte: $nuevoAC", 'info');
        mensaje("  - Guía Almacén: $guiaAC", 'info');
        mensaje("  - Guía Taller: {$resultadoTaller['guia']}", 'info');
        mensaje("  - Artículos procesados: " . count($articulos), 'info');
        mensaje("  - Total unidades: " . $totalUnidades, 'info');
        mensaje("  - Registros en taller: {$resultadoTaller['registros']}", 'info');
    } catch (Exception $e) {
        mensaje("", 'error');
        mensaje("============================================", 'error');
        mensaje("ERROR EN EL PROCESO", 'error');
        mensaje("============================================", 'error');
        mensaje("Error: " . $e->getMessage(), 'error');
        exit(1);
    }
}

// Ejecutar
procesarInventario();
