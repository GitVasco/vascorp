<?php
/**
 * Script para procesar inventario físico de Almacén de Corte
 * 
 * Este script:
 * 1. Lee un CSV con artículos y cantidades físicas
 * 2. Crea una Orden de Corte (si no existe)
 * 3. Crea un Almacén de Corte vinculado a la orden
 * 4. Actualiza stocks en articulojf
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

// Configuración
define('CSV_PATH', __DIR__ . '/csv/almacen_corte.csv');
define('USUARIO_ID', 6);
define('CONFIGURACION_DEFAULT', 'INV-' . date('Ymd'));

// Colores para output
define('COLOR_RESET', "\033[0m");
define('COLOR_SUCCESS', "\033[32m");
define('COLOR_ERROR', "\033[31m");
define('COLOR_INFO', "\033[36m");
define('COLOR_WARNING', "\033[33m");

/**
 * Función para mostrar mensajes con colores
 */
function mensaje($texto, $tipo = 'info') {
    $color = COLOR_INFO;
    switch($tipo) {
        case 'success': $color = COLOR_SUCCESS; break;
        case 'error': $color = COLOR_ERROR; break;
        case 'warning': $color = COLOR_WARNING; break;
    }
    echo $color . $texto . COLOR_RESET . "\n";
}

/**
 * Leer CSV y retornar array de artículos
 */
function leerCSV($archivo) {
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
    if (!$encabezados || (strtolower($encabezados[0]) !== 'articulo' && strtolower($encabezados[0]) !== 'artículo')) {
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
            $articulos[$articulo] += $cantidad;
            mensaje("Artículo $articulo: cantidad sumada (total: {$articulos[$articulo]})", 'info');
        } else {
            $articulos[$articulo] = $cantidad;
        }
    }
    
    fclose($handle);
    
    return $articulos;
}

/**
 * Obtener último código de orden de corte
 */
function obtenerUltimoCodigoOC() {
    $resultado = ModeloOrdenCorte::mdlUltimoId();
    if ($resultado && isset($resultado[0]['ult_codigo'])) {
        return intval($resultado[0]['ult_codigo']);
    }
    return 0;
}

/**
 * Obtener último código de almacén de corte
 */
function obtenerUltimoCodigoAC() {
    $resultado = ModeloAlmacenCorte::mdlUltimoCodigoAC();
    if ($resultado && isset($resultado['ultimo_codigo'])) {
        return intval($resultado['ultimo_codigo']);
    }
    return 0;
}

/**
 * Generar número de guía
 */
function generarGuia() {
    return 'GUIA-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
}

/**
 * Crear orden de corte
 */
function crearOrdenCorte($codigo, $articulos, $usuario, $configuracion) {
    $pdo = Conexion::conectar();
    $pdo->beginTransaction();
    
    try {
        // Calcular total
        $total = array_sum($articulos);
        
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
        foreach ($articulos as $articulo => $cantidad) {
            $artLimpio = trim($articulo); // Limpiar espacios
            
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
function crearAlmacenCorte($codigoAC, $codigoOC, $guia, $articulos, $usuario) {
    $pdo = Conexion::conectar();
    $pdo->beginTransaction();
    
    try {
        // Calcular total
        $total = array_sum($articulos);
        
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
        // Usar consulta directa para evitar problemas con LEFT JOIN
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
            $art = trim($detalle['articulo']); // Limpiar espacios
            $mapaDetalles[$art] = $detalle['id'];
        }
        
        // Debug: mostrar artículos encontrados vs esperados
        $articulosFaltantes = [];
        foreach ($articulos as $articulo => $cantidad) {
            $artLimpio = trim($articulo);
            if (!isset($mapaDetalles[$artLimpio])) {
                $articulosFaltantes[] = $artLimpio;
            }
        }
        
        if (!empty($articulosFaltantes)) {
            mensaje("Artículos en CSV pero no en Orden de Corte:", 'warning');
            foreach ($articulosFaltantes as $art) {
                mensaje("  - $art", 'warning');
            }
            mensaje("Artículos en Orden de Corte:", 'info');
            foreach (array_keys($mapaDetalles) as $art) {
                mensaje("  - $art", 'info');
            }
            throw new Exception("No se encontró detalle de orden de corte para artículo(es): " . implode(", ", $articulosFaltantes));
        }
        
        // Crear detalles de almacén de corte
        foreach ($articulos as $articulo => $cantidad) {
            $artLimpio = trim($articulo);
            if (!isset($mapaDetalles[$artLimpio])) {
                throw new Exception("No se encontró detalle de orden de corte para artículo $articulo");
            }
            
            $datosDetalle = [
                'almacencorte' => $codigoAC,
                'ordcorte' => $codigoOC,
                'idocd' => $mapaDetalles[$artLimpio],
                'articulo' => $artLimpio,
                'cantidad' => $cantidad
            ];
            
            $resultado = ModeloAlmacenCorte::mdlGuardarDetallesAlmacenCorte($datosDetalle);
            
            if ($resultado !== 'ok') {
                throw new Exception("Error al crear detalle de almacén de corte para artículo $articulo");
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
 * Función principal
 */
function procesarInventario() {
    try {
        mensaje("============================================", 'info');
        mensaje("PROCESADOR DE INVENTARIO - ALMACÉN DE CORTE", 'info');
        mensaje("============================================", 'info');
        mensaje("");
        
        // Leer CSV
        mensaje("Leyendo archivo CSV...", 'info');
        $articulos = leerCSV(CSV_PATH);
        
        if (empty($articulos)) {
            throw new Exception("El CSV está vacío o no contiene datos válidos");
        }
        
        mensaje("Artículos encontrados: " . count($articulos), 'success');
        mensaje("Total de unidades: " . array_sum($articulos), 'success');
        mensaje("");
        
        // Obtener códigos
        mensaje("Obteniendo códigos disponibles...", 'info');
        $ultimoOC = obtenerUltimoCodigoOC();
        $nuevoOC = $ultimoOC + 1;
        mensaje("Nueva Orden de Corte: $nuevoOC", 'info');
        
        $ultimoAC = obtenerUltimoCodigoAC();
        $nuevoAC = $ultimoAC + 1;
        mensaje("Nuevo Almacén de Corte: $nuevoAC", 'info');
        
        $guia = generarGuia();
        mensaje("Guía generada: $guia", 'info');
        mensaje("");
        
        // Crear orden de corte
        mensaje("Creando Orden de Corte...", 'info');
        crearOrdenCorte($nuevoOC, $articulos, USUARIO_ID, CONFIGURACION_DEFAULT);
        mensaje("✓ Orden de Corte creada exitosamente", 'success');
        mensaje("");
        
        // Crear almacén de corte
        mensaje("Creando Almacén de Corte...", 'info');
        crearAlmacenCorte($nuevoAC, $nuevoOC, $guia, $articulos, USUARIO_ID);
        mensaje("✓ Almacén de Corte creado exitosamente", 'success');
        mensaje("");
        
        mensaje("============================================", 'success');
        mensaje("PROCESO COMPLETADO EXITOSAMENTE", 'success');
        mensaje("============================================", 'success');
        mensaje("");
        mensaje("Resumen:", 'info');
        mensaje("  - Orden de Corte: $nuevoOC", 'info');
        mensaje("  - Almacén de Corte: $nuevoAC", 'info');
        mensaje("  - Guía: $guia", 'info');
        mensaje("  - Artículos procesados: " . count($articulos), 'info');
        mensaje("  - Total unidades: " . array_sum($articulos), 'info');
        
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

