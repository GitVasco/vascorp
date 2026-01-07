<?php

/**
 * Script para actualizar la columna alm_corte en articulojf
 * 
 * Este script recalcula la columna alm_corte basándose en:
 * - almacencorte_detallejf (saldo_taller > 0)
 * 
 * alm_corte = SUM(saldo_taller) de almacencorte_detallejf agrupado por articulo
 */

// Incluir conexión
require_once __DIR__ . '/../modelos/conexion.php';

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
function actualizarAlmCorte()
{
    try {
        mensaje("============================================", 'info');
        mensaje("ACTUALIZADOR DE ALM_CORTE", 'info');
        mensaje("============================================", 'info');
        mensaje("");

        $pdo = Conexion::conectar();
        
        // Iniciar transacción
        $pdo->beginTransaction();

        mensaje("Obteniendo suma de saldo_taller por artículo...", 'info');
        
        // Obtener suma de saldo_taller agrupado por artículo
        $stmt = $pdo->prepare("
            SELECT 
                articulo,
                SUM(saldo_taller) as total_saldo_taller
            FROM almacencorte_detallejf
            WHERE saldo_taller > 0
            GROUP BY articulo
        ");
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $totalArticulos = count($resultados);
        mensaje("  Encontrados $totalArticulos artículos con saldo_taller > 0", 'info');
        mensaje("");

        if ($totalArticulos == 0) {
            mensaje("No hay artículos con saldo_taller > 0 para actualizar", 'warning');
            $pdo->rollBack();
            return;
        }

        mensaje("Actualizando alm_corte en articulojf...", 'info');
        
        $actualizados = 0;
        $errores = 0;
        
        foreach ($resultados as $row) {
            $articulo = trim($row['articulo']);
            $totalSaldoTaller = intval($row['total_saldo_taller']);
            
            // Actualizar alm_corte en articulojf
            $stmt = $pdo->prepare("
                UPDATE articulojf 
                SET alm_corte = :total_saldo_taller
                WHERE articulo = :articulo
            ");
            $stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);
            $stmt->bindParam(":total_saldo_taller", $totalSaldoTaller, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $actualizados++;
            } else {
                $errores++;
                mensaje("  Error al actualizar artículo $articulo", 'error');
            }
            
            $stmt->closeCursor();
        }

        mensaje("  Actualizados: $actualizados artículos", 'success');
        if ($errores > 0) {
            mensaje("  Errores: $errores artículos", 'error');
        }
        mensaje("");

        // También poner en 0 los artículos que no tienen saldo_taller > 0 pero tienen alm_corte > 0
        mensaje("Limpiando artículos sin saldo_taller pero con alm_corte > 0...", 'info');
        
        $stmt = $pdo->prepare("
            UPDATE articulojf a
            LEFT JOIN (
                SELECT articulo, SUM(saldo_taller) as total_saldo_taller
                FROM almacencorte_detallejf
                WHERE saldo_taller > 0
                GROUP BY articulo
            ) acd ON a.articulo = acd.articulo
            SET a.alm_corte = 0
            WHERE a.alm_corte > 0 
            AND (acd.articulo IS NULL OR acd.total_saldo_taller IS NULL OR acd.total_saldo_taller = 0)
        ");
        $stmt->execute();
        $limpiados = $stmt->rowCount();
        $stmt->closeCursor();
        
        mensaje("  Limpiados: $limpiados artículos", 'info');
        mensaje("");

        // Confirmar transacción
        $pdo->commit();
        
        mensaje("✓ Actualización completada exitosamente", 'success');
        mensaje("");

        // Mostrar estadísticas
        mensaje("Estadísticas finales:", 'info');
        
        // Total de saldo_taller en almacencorte_detallejf
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT articulo) as articulos_distintos,
                SUM(saldo_taller) as total_saldo_taller
            FROM almacencorte_detallejf
            WHERE saldo_taller > 0
        ");
        $stmt->execute();
        $statsDetalle = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        
        // Total de alm_corte en articulojf
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as articulos_con_stock,
                SUM(alm_corte) as total_alm_corte
            FROM articulojf
            WHERE alm_corte > 0
        ");
        $stmt->execute();
        $statsArticulo = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        
        mensaje("  - Artículos distintos con saldo_taller > 0: " . number_format($statsDetalle['articulos_distintos'] ?? 0), 'info');
        mensaje("  - Total saldo_taller en almacencorte_detallejf: " . number_format($statsDetalle['total_saldo_taller'] ?? 0), 'info');
        mensaje("  - Artículos con alm_corte > 0: " . number_format($statsArticulo['articulos_con_stock'] ?? 0), 'info');
        mensaje("  - Total alm_corte en articulojf: " . number_format($statsArticulo['total_alm_corte'] ?? 0), 'info');
        
        $diferencia = abs(($statsDetalle['total_saldo_taller'] ?? 0) - ($statsArticulo['total_alm_corte'] ?? 0));
        if ($diferencia < 1) {
            mensaje("  ✓ Los totales coinciden correctamente", 'success');
        } else {
            mensaje("  ⚠ Diferencia entre totales: " . number_format($diferencia), 'warning');
        }

        mensaje("");
        mensaje("============================================", 'success');
        mensaje("PROCESO COMPLETADO EXITOSAMENTE", 'success');
        mensaje("============================================", 'success');
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        mensaje("", 'error');
        mensaje("============================================", 'error');
        mensaje("ERROR EN EL PROCESO", 'error');
        mensaje("============================================", 'error');
        mensaje("Error: " . $e->getMessage(), 'error');
        mensaje("Trace: " . $e->getTraceAsString(), 'error');
        exit(1);
    }
}

// Ejecutar
actualizarAlmCorte();

