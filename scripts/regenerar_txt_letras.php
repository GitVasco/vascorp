<?php

/**
 * Script para regenerar archivos TXT de letras
 *
 * Genera los archivos .txt a partir de envio_letra_cabecerajf y envio_letrasjf
 * cuando el TXT falló al crearse pero los registros ya están en la BD.
 *
 * Uso: php scripts/regenerar_txt_letras.php [codigo] [fecha]
 *   - Sin argumentos: regenera TODOS los envíos que tengan archivo en BD
 *   - codigo: solo el envío con ese código (ej: 1717)
 *   - fecha: solo envíos de esa fecha YYYY-MM-DD (ej: 2026-02-03)
 *
 * PHP 5 compatible
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$baseDir = dirname(__DIR__);
require_once $baseDir . '/modelos/conexion.php';

function eliminarTildes($cadena)
{
    if (empty($cadena)) return '';
    $cadena = str_replace(
        array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
        array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
        $cadena
    );
    $cadena = str_replace(
        array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
        array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
        $cadena
    );
    $cadena = str_replace(
        array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
        array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
        $cadena
    );
    $cadena = str_replace(
        array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
        array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
        $cadena
    );
    $cadena = str_replace(
        array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
        array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
        $cadena
    );
    $cadena = str_replace(
        array('ñ', 'Ñ', 'ç', 'Ç'),
        array('n', 'N', 'c', 'C'),
        $cadena
    );
    return $cadena;
}

function generarTxtLetras($baseDir, $codigoFiltro = null, $fechaFiltro = null)
{
    $pdo = Conexion::conectar();

    // Obtener cabeceras de envío
    $sql = "SELECT id, codigo, fecha, archivo FROM envio_letra_cabecerajf WHERE archivo IS NOT NULL AND archivo != ''";
    $params = array();

    if ($codigoFiltro !== null) {
        $sql .= " AND codigo = ?";
        $params[] = $codigoFiltro;
    }
    if ($fechaFiltro !== null) {
        $sql .= " AND fecha = ?";
        $params[] = $fechaFiltro;
    }

    $sql .= " ORDER BY id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cabeceras = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cabeceras)) {
        echo "No se encontraron envíos para regenerar.\n";
        return;
    }

    $carpetaLetras = $baseDir . '/vistas/letras';
    if (!is_dir($carpetaLetras)) {
        if (!mkdir($carpetaLetras, 0755, true)) {
            echo "ERROR: No se pudo crear la carpeta vistas/letras\n";
            return;
        }
        echo "Carpeta vistas/letras creada.\n";
    }

    foreach ($cabeceras as $cab) {
        $codigo = $cab['codigo'];
        $rutaArchivo = $baseDir . '/' . $cab['archivo'];

        // Obtener detalle de letras para este envío
        $stmt2 = $pdo->prepare("
            SELECT el.num_cta
            FROM envio_letrasjf el
            WHERE el.codigo = ?
            ORDER BY el.num_cta
        ");
        $stmt2->execute(array($codigo));
        $detalles = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        if (empty($detalles)) {
            echo "  [Cod $codigo] Sin detalle en envio_letrasjf, omitiendo.\n";
            continue;
        }

        $file = fopen($rutaArchivo, 'w');
        if ($file === false) {
            echo "  [Cod $codigo] ERROR: No se pudo crear archivo: $rutaArchivo\n";
            continue;
        }

        $lineasEscritas = 0;
        foreach ($detalles as $det) {
            $numCtaLimpio = $det['num_cta'];

            // Obtener datos de cuenta con cliente (tipo_doc 85 = letras)
            $stmt3 = $pdo->prepare("
                SELECT 
                    c.id,
                    c.num_cta,
                    c.fecha_ven,
                    c.saldo,
                    cli.nombres,
                    cli.ape_paterno,
                    cli.ape_materno,
                    cli.documento
                FROM cuenta_ctejf c
                LEFT JOIN clientesjf cli ON c.cliente = cli.codigo
                WHERE REPLACE(c.num_cta, '-', '') = ?
                AND c.tipo_doc = '85'
                AND c.tip_mov = '+'
                LIMIT 1
            ");
            $stmt3->execute(array($numCtaLimpio));
            $cuenta = $stmt3->fetch(PDO::FETCH_ASSOC);

            if (!$cuenta) {
                echo "  [Cod $codigo] Advertencia: cuenta num_cta=$numCtaLimpio no encontrada, omitiendo línea.\n";
                continue;
            }

            $doc = $cuenta['documento'] ? trim($cuenta['documento']) : '';
            if (strlen($doc) == 11) {
                $cliente_doc = '6' . $doc;
            } else {
                $cliente_doc = '1' . $doc;
            }

            $cliente_nom = eliminarTildes($cuenta['nombres'] ? $cuenta['nombres'] : '');
            $cliente_pat = eliminarTildes($cuenta['ape_paterno'] ? $cuenta['ape_paterno'] : '');
            $cliente_mat = eliminarTildes($cuenta['ape_materno'] ? $cuenta['ape_materno'] : '');

            $salto1 = 72;
            $salto2 = 24;
            $salto3 = 24;
            $salto4 = 16;
            $salto5 = 12;
            $monto = number_format($cuenta['saldo'], 2, '.', '');
            $salto6 = (strlen($monto) == 7) ? 13 : 14;

            $fechaVen = $cuenta['fecha_ven'];
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $fechaVen, $m)) {
                $fechaTxt = $m[3] . $m[2] . substr($m[1], 2);
            } else {
                $fechaTxt = str_replace(array('-', '/'), '', $fechaVen);
            }

            $linea = str_pad($cliente_nom, $salto1) .
                str_pad($cliente_pat, $salto2) .
                str_pad($cliente_mat, $salto3) .
                str_pad($cliente_doc, $salto4) .
                str_pad($numCtaLimpio, $salto5) .
                str_pad($fechaTxt, $salto6) . $monto . PHP_EOL;

            fwrite($file, $linea);
            $lineasEscritas++;
        }

        fclose($file);
        echo "  [Cod $codigo] OK: $rutaArchivo ($lineasEscritas líneas)\n";
    }
}

// --- Ejecución ---
$codigo = null;
$fecha = null;
if (isset($argv[1])) {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $argv[1])) {
        $fecha = $argv[1];
    } else {
        $codigo = $argv[1];
    }
}
if (isset($argv[2])) {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $argv[2])) {
        $fecha = $argv[2];
    } else {
        $codigo = $argv[2];
    }
}

echo "Regenerando archivos TXT de letras...\n";
if ($codigo) echo "Filtro código: $codigo\n";
if ($fecha) echo "Filtro fecha: $fecha\n";
echo "---\n";

generarTxtLetras($baseDir, $codigo, $fecha);

echo "Listo.\n";
