<?php

/**
 * Script para actualizar la columna servicio en articulojf
 * 
 * Este script recalcula la columna servicio basándose en:
 * - servicios_detallejf (saldo > 0 y cerrar = 0)
 * - cierres_detallejf (cantidad > 0)
 * 
 * servicio = servicios + cierres
 */

// Incluir conexión y modelos
require_once __DIR__ . '/../modelos/conexion.php';
require_once __DIR__ . '/../modelos/cierres.modelo.php';

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
 * Función principal
 */
function actualizarServicioTotal()
{
    try {
        mensaje("============================================", 'info');
        mensaje("ACTUALIZADOR DE SERVICIO TOTAL", 'info');
        mensaje("============================================", 'info');
        mensaje("");

        mensaje("Recalculando servicio = servicios + cierres...", 'info');
        mensaje("");

        // Usar el método del modelo que ya existe
        $resultado = ModeloCierres::mdlActualizarServicioTotal();

        if ($resultado === 'ok') {
            mensaje("✓ Servicio total actualizado exitosamente", 'success');
            mensaje("");
            
            // Mostrar estadísticas
            $pdo = Conexion::conectar();
            
            // Contar artículos con servicio > 0
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM articulojf WHERE servicio > 0");
            $stmt->execute();
            $conServicio = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Sumar total de servicios activos
            $stmt = $pdo->prepare("SELECT COUNT(DISTINCT articulo) as total, SUM(saldo) as suma 
                FROM servicios_detallejf 
                WHERE cerrar = 0 AND saldo > 0");
            $stmt->execute();
            $servicios = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Sumar total de cierres
            $stmt = $pdo->prepare("SELECT COUNT(DISTINCT articulo) as total, SUM(cantidad) as suma 
                FROM cierres_detallejf 
                WHERE cantidad > 0");
            $stmt->execute();
            $cierres = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Sumar total en articulojf
            $stmt = $pdo->prepare("SELECT SUM(servicio) as suma FROM articulojf WHERE servicio > 0");
            $stmt->execute();
            $totalServicio = $stmt->fetch(PDO::FETCH_ASSOC);
            
            mensaje("Estadísticas:", 'info');
            mensaje("  - Artículos con servicio > 0: " . number_format($conServicio['total']), 'info');
            mensaje("  - Artículos en servicios activos: " . number_format($servicios['total']), 'info');
            mensaje("  - Total unidades en servicios: " . number_format($servicios['suma'] ?? 0), 'info');
            mensaje("  - Artículos en cierres: " . number_format($cierres['total']), 'info');
            mensaje("  - Total unidades en cierres: " . number_format($cierres['suma'] ?? 0), 'info');
            mensaje("  - Total servicio en articulojf: " . number_format($totalServicio['suma'] ?? 0), 'info');
            
            $sumaEsperada = ($servicios['suma'] ?? 0) + ($cierres['suma'] ?? 0);
            mensaje("  - Suma esperada (servicios + cierres): " . number_format($sumaEsperada), 'info');
            
            if (abs(($totalServicio['suma'] ?? 0) - $sumaEsperada) < 1) {
                mensaje("✓ Los totales coinciden correctamente", 'success');
            } else {
                mensaje("⚠ Hay una diferencia entre los totales", 'warning');
            }
            
        } else {
            throw new Exception("Error al actualizar servicio total");
        }

        mensaje("");
        mensaje("============================================", 'success');
        mensaje("PROCESO COMPLETADO EXITOSAMENTE", 'success');
        mensaje("============================================", 'success');
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
actualizarServicioTotal();

