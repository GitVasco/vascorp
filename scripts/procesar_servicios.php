<?php

/**
 * Script para procesar inventario físico de Servicios
 * 
 * Este script:
 * 1. Lee un CSV con artículos, cantidades y talleres externos
 * 2. Crea Orden de Corte OBLIGATORIAMENTE (grupo independiente)
 * 3. Crea Almacén de Corte OBLIGATORIAMENTE (grupo independiente)
 * 4. Registra en entaller_cabjf vinculado a almacencorte_detallejf (uno por cada taller)
 * 5. Actualiza stocks (taller y alm_corte)
 * 
 * Formato CSV requerido:
 * articulo,cantidad,taller
 * ART001,50,TALLER01
 * ART001,30,TALLER02
 * ART002,25,TALLER01
 */

// Incluir conexión y modelos
require_once __DIR__ . '/../modelos/conexion.php';
require_once __DIR__ . '/../modelos/ordencorte.modelo.php';
require_once __DIR__ . '/../modelos/almacencorte.modelo.php';
require_once __DIR__ . '/../modelos/articulos.modelo.php';
require_once __DIR__ . '/../modelos/cortes.modelo.php';
require_once __DIR__ . '/../modelos/servicio.modelo.php';

// Configuración
define('CSV_PATH', __DIR__ . '/csv/servicios.csv');
define('USUARIO_ID', 6);
define('CONFIGURACION_DEFAULT', 'INV-SERVICIOS-' . date('Ymd'));

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
 * Mantiene cada línea con su taller específico para registro en entaller_cabjf
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
    if (!$encabezados || strtolower($encabezados[0]) !== 'articulo' || 
        strtolower($encabezados[1]) !== 'cantidad' || 
        strtolower($encabezados[2]) !== 'taller') {
        throw new Exception("El CSV debe tener encabezados: articulo,cantidad,taller");
    }

    $linea = 1;
    while (($fila = fgetcsv($handle)) !== false) {
        $linea++;

        if (count($fila) < 3) {
            mensaje("Advertencia: Línea $linea ignorada (formato incorrecto)", 'warning');
            continue;
        }

        $articulo = trim($fila[0]);
        $cantidad = intval(trim($fila[1]));
        $taller = trim($fila[2]);

        if (empty($articulo) || $cantidad <= 0 || empty($taller)) {
            mensaje("Advertencia: Línea $linea ignorada (artículo, cantidad o taller inválido)", 'warning');
            continue;
        }

        // Mantener cada línea con su taller específico
        $articulos[] = [
            'articulo' => $articulo,
            'cantidad' => $cantidad,
            'taller' => $taller
        ];
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
    return 'GUIA-SERVICIOS-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
}

/**
 * Obtener último código de servicio para un taller
 */
function obtenerUltimoCodigoServicio($taller)
{
    $resultado = ModeloServicios::mdlUltimoServicio('serviciosjf');
    if ($resultado && isset($resultado['ultimo_codigo'])) {
        $ultimoNumero = intval($resultado['ultimo_codigo']);
        // Formato: TALLER + número de 4 dígitos (ej: T40001, T60001)
        return $taller . str_pad($ultimoNumero, 4, '0', STR_PAD_LEFT);
    }
    return $taller . '0001';
}

/**
 * Crear orden de corte
 * Agrupa artículos únicos sumando cantidades
 */
function crearOrdenCorte($codigo, $articulos, $usuario, $configuracion)
{
    $pdo = Conexion::conectar();
    $pdo->beginTransaction();

    try {
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

        // Calcular total (sumar todas las cantidades)
        $total = array_sum($articulosUnicos);

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
 * Agrupa artículos únicos sumando cantidades
 */
function crearAlmacenCorte($codigoAC, $codigoOC, $guia, $articulos, $usuario)
{
    $pdo = Conexion::conectar();
    $pdo->beginTransaction();

    try {
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

        // Calcular total (sumar todas las cantidades)
        $total = array_sum($articulosUnicos);

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
 * Registrar artículos en servicios (entaller_cabjf)
 * Registra cada línea con su taller específico
 */
function registrarEnServicios($articulos, $codigoAC, $usuario)
{
    $pdo = Conexion::conectar();
    $pdo->beginTransaction();

    try {
        $guia = generarGuia();
        $registrosCreados = 0;
        $articulosConIds = []; // Array para guardar artículos con sus IDs de entaller_cabjf

        foreach ($articulos as $item) {
            $articulo = trim($item['articulo']);
            $cantidad = $item['cantidad'];
            $taller = trim($item['taller']);

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

            // Insertar en entaller_cabjf con el taller específico de esta línea
            $stmt = $pdo->prepare("INSERT INTO entaller_cabjf
                (articulo, usuario, cantidad, saldo, estado, guia, taller, almacencorte_detalle_id) 
                VALUES
                (:articulo, :usuario, :cantidad, :saldo, :estado, :guia, :taller, :almacencorte_detalle_id)");

            $estado = '0'; // Pendiente
            $stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);
            $stmt->bindParam(":usuario", $usuario, PDO::PARAM_INT);
            $stmt->bindParam(":cantidad", $cantidadAUsar, PDO::PARAM_INT);
            $stmt->bindParam(":saldo", $cantidadAUsar, PDO::PARAM_INT);
            $stmt->bindParam(":estado", $estado, PDO::PARAM_STR);
            $stmt->bindParam(":guia", $guia, PDO::PARAM_STR);
            $stmt->bindParam(":taller", $taller, PDO::PARAM_STR);
            $stmt->bindParam(":almacencorte_detalle_id", $detalleId, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                throw new Exception("Error al registrar artículo $articulo en taller $taller");
            }

            // Obtener el ID del registro recién creado en entaller_cabjf
            $entallerCabId = $pdo->lastInsertId();
            
            // Guardar el artículo con su ID para usarlo después en servicios_detallejf
            $articulosConIds[] = [
                'articulo' => $articulo,
                'cantidad' => $cantidadAUsar,
                'taller' => $taller,
                'entaller_cab_id' => $entallerCabId
            ];

            // Actualizar saldo_taller del detalle
            $nuevoSaldo = max(0, $saldoDisponible - $cantidadAUsar);
            $stmt = $pdo->prepare("UPDATE almacencorte_detallejf
                SET saldo_taller = :nuevo_saldo
                WHERE id = :detalle_id");
            $stmt->bindParam(":nuevo_saldo", $nuevoSaldo, PDO::PARAM_INT);
            $stmt->bindParam(":detalle_id", $detalleId, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();

            // Actualizar articulojf: disminuir taller (alm_corte ya se descontó antes)
            $stmt = $pdo->prepare("UPDATE articulojf 
                SET taller = GREATEST(taller - :cantidad, 0)
                WHERE articulo = :articulo");
            $stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);
            $stmt->bindParam(":cantidad", $cantidadAUsar, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();

            $registrosCreados++;
        }

        // Agrupar por taller para crear cabeceras de servicios
        $articulosPorTaller = [];
        foreach ($articulosConIds as $item) {
            $taller = trim($item['taller']);
            if (!isset($articulosPorTaller[$taller])) {
                $articulosPorTaller[$taller] = [];
            }
            $articulosPorTaller[$taller][] = $item;
        }

        // Crear cabeceras y detalles de servicios por cada taller
        $serviciosCreados = [];
        foreach ($articulosPorTaller as $taller => $itemsTaller) {
            // Obtener último código de servicio para este taller
            $codigoServicio = obtenerUltimoCodigoServicio($taller);
            
            // Calcular total para este taller
            $totalTaller = 0;
            foreach ($itemsTaller as $item) {
                $totalTaller += $item['cantidad'];
            }

            // Crear cabecera de servicio
            $fecha = date('Y-m-d H:i:s');
            $datosServicio = [
                'codigo' => $codigoServicio,
                'usuario' => $usuario,
                'taller' => $taller,
                'total' => $totalTaller,
                'fecha' => $fecha,
                'estado' => 'ACTIVO'
            ];

            $resultado = ModeloServicios::mdlGuardarServicios('serviciosjf', $datosServicio);
            
            if ($resultado !== 'ok') {
                throw new Exception("Error al crear cabecera de servicio para taller $taller");
            }

            // Crear detalles de servicio y actualizar stocks
            foreach ($itemsTaller as $item) {
                $articulo = trim($item['articulo']);
                $cantidad = $item['cantidad'];
                $entallerCabId = isset($item['entaller_cab_id']) ? $item['entaller_cab_id'] : null;

                // Crear detalle de servicio
                $datosDetalle = [
                    'codigo' => $codigoServicio,
                    'articulo' => $articulo,
                    'cantidad' => $cantidad,
                    'saldo' => $cantidad,
                    'cabecera_taller' => $entallerCabId
                ];

                ModeloServicios::mdlGuardarDetallesServicios('servicios_detallejf', $datosDetalle);

                // Actualizar articulojf: sumar a servicio
                $stmt = $pdo->prepare("UPDATE articulojf 
                    SET servicio = servicio + :cantidad
                    WHERE articulo = :articulo");
                $stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);
                $stmt->bindParam(":cantidad", $cantidad, PDO::PARAM_INT);
                $stmt->execute();
                $stmt->closeCursor();
            }

            $serviciosCreados[] = [
                'codigo' => $codigoServicio,
                'taller' => $taller,
                'total' => $totalTaller
            ];
        }

        $pdo->commit();
        return [
            'registros' => $registrosCreados, 
            'guia' => $guia,
            'servicios' => $serviciosCreados
        ];
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
        mensaje("PROCESADOR DE INVENTARIO - SERVICIOS", 'info');
        mensaje("============================================", 'info');
        mensaje("");

        // Leer CSV
        mensaje("Leyendo archivo CSV...", 'info');
        $articulos = leerCSV(CSV_PATH);

        if (empty($articulos)) {
            throw new Exception("El CSV está vacío o no contiene datos válidos");
        }

        mensaje("Líneas encontradas: " . count($articulos), 'success');
        
        // Contar artículos únicos y talleres únicos
        $articulosUnicos = [];
        $talleresUnicos = [];
        $totalUnidades = 0;
        foreach ($articulos as $item) {
            $art = trim($item['articulo']);
            $taller = trim($item['taller']);
            if (!isset($articulosUnicos[$art])) {
                $articulosUnicos[$art] = 0;
            }
            $articulosUnicos[$art] += $item['cantidad'];
            $talleresUnicos[$taller] = true;
            $totalUnidades += $item['cantidad'];
        }
        
        mensaje("Artículos únicos: " . count($articulosUnicos), 'success');
        mensaje("Talleres únicos: " . count($talleresUnicos), 'success');
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

        // Registrar en servicios
        mensaje("Registrando artículos en Servicios...", 'info');
        $resultadoServicios = registrarEnServicios($articulos, $nuevoAC, USUARIO_ID);
        mensaje("✓ Registrados {$resultadoServicios['registros']} registros en entaller_cabjf", 'success');
        mensaje("  Guía de servicios: {$resultadoServicios['guia']}", 'info');
        
        // Mostrar servicios creados
        mensaje("✓ Servicios creados:", 'success');
        foreach ($resultadoServicios['servicios'] as $servicio) {
            mensaje("  - {$servicio['codigo']} (Taller: {$servicio['taller']}, Total: {$servicio['total']})", 'info');
        }
        mensaje("");

        mensaje("============================================", 'success');
        mensaje("PROCESO COMPLETADO EXITOSAMENTE", 'success');
        mensaje("============================================", 'success');
        mensaje("");
        mensaje("Resumen:", 'info');
        mensaje("  - Orden de Corte: $nuevoOC", 'info');
        mensaje("  - Almacén de Corte: $nuevoAC", 'info');
        mensaje("  - Guía Almacén: $guiaAC", 'info');
        mensaje("  - Guía Servicios: {$resultadoServicios['guia']}", 'info');
        mensaje("  - Artículos únicos: " . count($articulosUnicos), 'info');
        mensaje("  - Talleres únicos: " . count($talleresUnicos), 'info');
        mensaje("  - Total unidades: " . $totalUnidades, 'info');
        mensaje("  - Registros en entaller_cabjf: {$resultadoServicios['registros']}", 'info');
        mensaje("  - Servicios creados: " . count($resultadoServicios['servicios']), 'info');
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

