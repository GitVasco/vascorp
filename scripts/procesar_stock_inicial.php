<?php

/**
 * Script para procesar stock inicial
 * 
 * Este script:
 * 1. Lee un CSV con artículos y cantidades
 * 2. PRIMERO actualiza los stocks en articulojf (stock y stock01)
 * 3. LUEGO hace un INSERT BULK de todos los artículos a movimientosjf_2026
 * 
 * Formato CSV requerido:
 * articulo,cantidad
 * ART001,50
 * ART002,30
 * ART003,25
 * 
 * Configuración:
 * - Almacén siempre: 01
 * - Fecha fija: 2026-01-01
 * - Documento: INV01202601 (INV + mes(01) + año(2026) + número)
 */

// Incluir conexión y modelos
require_once __DIR__ . '/../modelos/conexion.php';
require_once __DIR__ . '/../modelos/articulos.modelo.php';

// Configuración
define('CSV_PATH', __DIR__ . '/csv/stock_inicial.csv');
define('ANIO', '2026');
define('FECHA_FIJA', '2026-01-01');
define('ALMACEN_FIJO', '01');
define('MES', '01');

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
 * Leer CSV y retornar array de artículos (solo articulo y cantidad)
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
        strtolower($encabezados[1]) !== 'cantidad') {
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
        $cantidad = floatval(trim($fila[1])); // Usar floatval por si hay decimales

        if (empty($articulo) || $cantidad < 0) {
            mensaje("Advertencia: Línea $linea ignorada (artículo o cantidad inválido)", 'warning');
            continue;
        }

        // Agrupar por artículo (sumar cantidades si se repite)
        if (isset($articulos[$articulo])) {
            $articulos[$articulo] += $cantidad;
        } else {
            $articulos[$articulo] = $cantidad;
        }
    }

    fclose($handle);

    return $articulos;
}

/**
 * Obtener último número de documento de inventario inicial para enero 2026
 * Formato del documento: INV + mes (2 dígitos) + año (4 dígitos) + número (2 dígitos)
 * Ejemplo: INV01202601 (INV + 01 + 2026 + 01)
 */
function obtenerUltimoNumeroDocumento($anio, $mes)
{
    $pdo = Conexion::conectar();
    $tabla = "movimientosjf_$anio";
    
    // Verificar si la tabla existe
    $stmt = $pdo->prepare("SHOW TABLES LIKE :tabla");
    $stmt->bindParam(":tabla", $tabla, PDO::PARAM_STR);
    $stmt->execute();
    $existe = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existe) {
        return 0; // Si no existe la tabla, empezar desde 1
    }
    
    // Buscar el último documento que empiece con INV + mes + año
    $patron = "INV" . str_pad($mes, 2, '0', STR_PAD_LEFT) . $anio . "%";
    $stmt = $pdo->prepare("SELECT documento FROM $tabla 
        WHERE documento LIKE :patron 
        AND tipo = 'E01' 
        AND nombre_tipo = 'INVENTARIO INICIAL'
        ORDER BY documento DESC LIMIT 1");
    $stmt->bindParam(":patron", $patron, PDO::PARAM_STR);
    $stmt->execute();
    $ultimo = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    
    if ($ultimo && $ultimo['documento']) {
        // Extraer el número del documento (ej: INV01202601 -> 01)
        $numero = substr($ultimo['documento'], -2);
        return intval($numero);
    }
    
    return 0;
}

/**
 * Generar número de documento de inventario inicial
 * Formato: INV + mes (2 dígitos) + año (4 dígitos) + número (2 dígitos)
 * Ejemplo: INV01202601 (INV + 01 + 2026 + 01)
 */
function generarDocumentoInventario($anio, $mes)
{
    $ultimoNumero = obtenerUltimoNumeroDocumento($anio, $mes);
    $nuevoNumero = $ultimoNumero + 1;
    $mesFormateado = str_pad($mes, 2, '0', STR_PAD_LEFT);
    
    return 'INV' . $mesFormateado . $anio . str_pad($nuevoNumero, 2, '0', STR_PAD_LEFT);
}

/**
 * Actualizar stocks en articulojf (PRIMERO)
 */
function actualizarStocks($articulos)
{
    $pdo = Conexion::conectar();
    $pdo->beginTransaction();

    try {
        mensaje("Actualizando stocks en articulojf...", 'info');
        
        $columnaAlmacen = 'stock01'; // Siempre almacén 01
        $actualizados = 0;
        $errores = [];

        foreach ($articulos as $articulo => $cantidad) {
            $articulo = trim($articulo);
            
            // 1. Actualizar stock general
            $stmt = $pdo->prepare("UPDATE articulojf 
                SET stock = stock + :cantidad
                WHERE articulo = :articulo");
            $stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);
            $stmt->bindParam(":cantidad", $cantidad, PDO::PARAM_STR);
            
            if (!$stmt->execute()) {
                $errores[] = "Error actualizando stock general para artículo $articulo";
                continue;
            }
            $stmt->closeCursor();

            // 2. Actualizar stock01 (almacén 01)
            // Verificar que la columna existe antes de actualizar
            $stmt = $pdo->prepare("SHOW COLUMNS FROM articulojf LIKE :columna");
            $stmt->bindParam(":columna", $columnaAlmacen, PDO::PARAM_STR);
            $stmt->execute();
            $columnaExiste = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if ($columnaExiste) {
                $sql = "UPDATE articulojf 
                    SET $columnaAlmacen = $columnaAlmacen + :cantidad
                    WHERE articulo = :articulo";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(":cantidad", $cantidad, PDO::PARAM_STR);
                $stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);
                
                if (!$stmt->execute()) {
                    $errores[] = "Error actualizando stock01 para artículo $articulo";
                }
                $stmt->closeCursor();
            } else {
                $errores[] = "Advertencia: Columna $columnaAlmacen no existe para artículo $articulo";
            }

            $actualizados++;
            
            if ($actualizados % 100 == 0) {
                mensaje("  Actualizados $actualizados artículos...", 'info');
            }
        }

        if (!empty($errores)) {
            foreach ($errores as $error) {
                mensaje($error, 'warning');
            }
        }

        $pdo->commit();
        mensaje("✓ Stocks actualizados: $actualizados artículos", 'success');
        mensaje("");
        
        return $actualizados;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Insertar movimientos en BULK (DESPUÉS de actualizar stocks)
 */
function insertarMovimientosBulk($articulos, $documento, $fecha, $almacen, $anio)
{
    $pdo = Conexion::conectar();
    $pdo->beginTransaction();

    try {
        $tabla = "movimientosjf_$anio";
        $registrosCreados = 0;
        
        mensaje("Insertando movimientos en $tabla (BULK)...", 'info');
        
        // Preparar valores para INSERT BULK
        $valores = [];
        $tipo = 'E01';
        $nombre_tipo = 'INVENTARIO INICIAL';
        $taller = null;
        $linea = null;
        $cliente = null;
        $vendedor = null;
        $precio = 0;
        $dscto1 = 0;
        $dscto2 = 0;
        $total = 0;
        $idcierre = null;
        $corte = null;

        // Construir valores para cada artículo
        foreach ($articulos as $articulo => $cantidad) {
            $articulo = trim($articulo);
            
            // Escapar valores para SQL
            $articuloEscapado = $pdo->quote($articulo);
            $documentoEscapado = $pdo->quote($documento);
            $fechaEscapada = $pdo->quote($fecha);
            $almacenEscapado = $pdo->quote($almacen);
            $tipoEscapado = $pdo->quote($tipo);
            $nombreTipoEscapado = $pdo->quote($nombre_tipo);
            
            $valores[] = "($tipoEscapado, $documentoEscapado, NULL, $fechaEscapada, $articuloEscapado, NULL, NULL, NULL, 
                $cantidad, $precio, $dscto1, $dscto2, $total, $nombreTipoEscapado, $almacenEscapado, NULL, NULL)";
        }

        // INSERT BULK
        if (!empty($valores)) {
            $sql = "INSERT INTO $tabla
                (tipo, documento, taller, fecha, articulo, linea, cliente, vendedor, 
                 cantidad, precio, dscto1, dscto2, total, nombre_tipo, almacen, idcierre, corte) 
                VALUES " . implode(', ', $valores);
            
            $stmt = $pdo->prepare($sql);
            
            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Error en INSERT BULK: " . print_r($errorInfo, true));
            }
            
            $registrosCreados = count($valores);
            $stmt->closeCursor();
        }

        $pdo->commit();
        mensaje("✓ Movimientos insertados: $registrosCreados registros", 'success');
        mensaje("");
        
        return $registrosCreados;
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
        mensaje("PROCESADOR DE STOCK INICIAL", 'info');
        mensaje("============================================", 'info');
        mensaje("");

        // Leer CSV
        mensaje("Leyendo archivo CSV...", 'info');
        $articulos = leerCSV(CSV_PATH);

        if (empty($articulos)) {
            throw new Exception("El CSV está vacío o no contiene datos válidos");
        }

        mensaje("Artículos encontrados: " . count($articulos), 'success');
        
        // Calcular total de unidades
        $totalUnidades = array_sum($articulos);
        mensaje("Total de unidades: " . number_format($totalUnidades, 2), 'success');
        mensaje("");

        // Generar documento
        $documento = generarDocumentoInventario(ANIO, MES);
        mensaje("Documento generado: $documento", 'info');
        mensaje("Fecha: " . FECHA_FIJA, 'info');
        mensaje("Almacén: " . ALMACEN_FIJO, 'info');
        mensaje("");

        // PASO 1: Actualizar stocks primero
        $actualizados = actualizarStocks($articulos);

        // PASO 2: Insertar movimientos en BULK después
        $registrosCreados = insertarMovimientosBulk($articulos, $documento, FECHA_FIJA, ALMACEN_FIJO, ANIO);

        mensaje("============================================", 'success');
        mensaje("PROCESO COMPLETADO EXITOSAMENTE", 'success');
        mensaje("============================================", 'success');
        mensaje("");
        mensaje("Resumen:", 'info');
        mensaje("  - Documento: $documento", 'info');
        mensaje("  - Fecha: " . FECHA_FIJA, 'info');
        mensaje("  - Almacén: " . ALMACEN_FIJO, 'info');
        mensaje("  - Tabla: movimientosjf_" . ANIO, 'info');
        mensaje("  - Artículos únicos: " . count($articulos), 'info');
        mensaje("  - Total unidades: " . number_format($totalUnidades, 2), 'info');
        mensaje("  - Stocks actualizados: $actualizados", 'info');
        mensaje("  - Movimientos creados: $registrosCreados", 'info');
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
