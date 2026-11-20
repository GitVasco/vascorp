<?php

/**
 * Script para regenerar planillas Excel de letras (formato BCP Cobranza Libre).
 *
 * Genera los archivos .xlsx a partir de envio_letra_cabecerajf y envio_letrasjf
 * cuando el Excel falló al crearse pero los registros ya están en la BD.
 * También puede regenerar envíos antiguos (.txt / .xlsx).
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
require_once $baseDir . '/controladores/envio-letras-excel.lib.php';

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

function generarExcelLetras($baseDir, $codigoFiltro = null, $fechaFiltro = null)
{
    $pdo = Conexion::conectar();

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
        $archivoBd = $cab['archivo'];
        $codigoArchivo = preg_replace('/[^0-9A-Za-z]/', '', (string) $codigo);
        $rutaRel = 'vistas/letras/L' . $codigoArchivo . '_' . date('YmdHis') . '.xlsx';
        $rutaArchivo = $baseDir . '/' . $rutaRel;

        $stmt2 = $pdo->prepare("
            SELECT el.num_cta
            FROM envio_letrasjf el
            WHERE el.codigo = ?
            ORDER BY el.id ASC
        ");
        $stmt2->execute(array($codigo));
        $detalles = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        if (empty($detalles)) {
            echo "  [Cod $codigo] Sin detalle en envio_letrasjf, omitiendo.\n";
            continue;
        }

        $filasExcel = array();
        $faltan = 0;
        foreach ($detalles as $det) {
            $numCtaLimpio = $det['num_cta'];

            $stmt3 = $pdo->prepare("
                SELECT 
                    c.id,
                    c.num_cta,
                    c.fecha_ven,
                    c.saldo,
                    cli.nombre AS cliente_nombre,
                    cli.nombres,
                    cli.ape_paterno,
                    cli.ape_materno,
                    cli.documento,
                    cli.direccion,
                    UPPER(TRIM(IFNULL(u.Departamento, ''))) AS departamento,
                    UPPER(TRIM(IFNULL(u.provincia, ''))) AS provincia,
                    UPPER(TRIM(IFNULL(u.distrito, ''))) AS distrito
                FROM cuenta_ctejf c
                LEFT JOIN clientesjf cli ON c.cliente = cli.codigo
                LEFT JOIN ubigeo u ON cli.ubigeo = u.codigo
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
            $tipoDoc = (strlen($doc) == 11) ? 6 : 1;

            $direccion = eliminarTildes($cuenta['direccion'] ? trim($cuenta['direccion']) : '');
            $departamento = eliminarTildes($cuenta['departamento'] ? trim($cuenta['departamento']) : '');
            $provincia = eliminarTildes($cuenta['provincia'] ? trim($cuenta['provincia']) : '');
            $distrito = eliminarTildes($cuenta['distrito'] ? trim($cuenta['distrito']) : '');

            if ($direccion === '' || $departamento === '' || $provincia === '' || $distrito === '') {
                $faltan++;
            }

            $partesNom = EnvioLetrasExcelLib::partesNombreCliente(
                $cuenta['nombres'] ? $cuenta['nombres'] : '',
                $cuenta['ape_paterno'] ? $cuenta['ape_paterno'] : '',
                $cuenta['ape_materno'] ? $cuenta['ape_materno'] : '',
                $cuenta['cliente_nombre'] ? $cuenta['cliente_nombre'] : '',
                $doc,
                $tipoDoc
            );

            $filasExcel[] = array(
                'nombre' => eliminarTildes($partesNom['nombre']),
                'ape_paterno' => eliminarTildes($partesNom['ape_paterno']),
                'ape_materno' => eliminarTildes($partesNom['ape_materno']),
                'tipo_doc' => $tipoDoc,
                'num_doc' => $doc,
                'num_letra' => $numCtaLimpio,
                'fecha_ven' => EnvioLetrasExcelLib::formatearFecha($cuenta['fecha_ven']),
                'importe' => $cuenta['saldo'],
                'direccion' => $direccion,
                'departamento' => $departamento,
                'provincia' => $provincia,
                'distrito' => $distrito
            );
        }

        if (empty($filasExcel)) {
            echo "  [Cod $codigo] Sin filas para Excel, omitiendo.\n";
            continue;
        }

        $ok = EnvioLetrasExcelLib::generar($rutaArchivo, $filasExcel);
        if (!$ok) {
            echo "  [Cod $codigo] ERROR: No se pudo crear archivo: $rutaArchivo\n";
            continue;
        }

        if ($rutaRel !== $archivoBd) {
            $upd = $pdo->prepare("UPDATE envio_letra_cabecerajf SET archivo = ? WHERE id = ?");
            $upd->execute(array($rutaRel, $cab['id']));
        }

        $aviso = $faltan > 0 ? " ($faltan sin dirección completa)" : '';
        echo "  [Cod $codigo] OK: $rutaArchivo (" . count($filasExcel) . " filas)$aviso\n";
    }
}

// --- Ejecución ---
$codigo = null;
$fecha = null;
if (isset($argv[1])) {
    if (preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $argv[1])) {
        $fecha = $argv[1];
    } else {
        $codigo = $argv[1];
    }
}
if (isset($argv[2])) {
    if (preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $argv[2])) {
        $fecha = $argv[2];
    } else {
        $codigo = $argv[2];
    }
}

echo "Regenerando planillas Excel de letras...\n";
if ($codigo) echo "Filtro código: $codigo\n";
if ($fecha) echo "Filtro fecha: $fecha\n";
echo "---\n";

generarExcelLetras($baseDir, $codigo, $fecha);

echo "Listo.\n";
