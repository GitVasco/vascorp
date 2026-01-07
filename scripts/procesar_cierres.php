<?php

/**
 * Script para procesar inventario físico de Cierres
 * 
 * Este script:
 * 1. Lee un CSV con artículos, cantidades y talleres
 * 2. Crea Orden de Corte OBLIGATORIAMENTE (grupo independiente)
 * 3. Crea Almacén de Corte OBLIGATORIAMENTE (grupo independiente)
 * 4. Busca servicios_detallejf existentes para heredar sus IDs
 * 5. Crea cabeceras en cierresjf agrupadas por taller
 * 6. Crea detalles en cierres_detallejf vinculados a servicios_detallejf
 * 7. Actualiza stocks: suma a servicio en articulojf
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
require_once __DIR__ . '/../modelos/cierres.modelo.php';
require_once __DIR__ . '/../modelos/servicio.modelo.php';

// Configuración
define('CSV_PATH', __DIR__ . '/csv/cierres.csv');
define('USUARIO_ID', 6);
define('CONFIGURACION_DEFAULT', 'INV-CIERRES-' . date('Ymd'));

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
    if (
        !$encabezados || strtolower($encabezados[0]) !== 'articulo' ||
        strtolower($encabezados[1]) !== 'cantidad' ||
        strtolower($encabezados[2]) !== 'taller'
    ) {
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
    return 'GUIA-CIERRES-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
}

/**
 * Obtener último código de cierre para un taller
 */
function obtenerUltimoCodigoCierre($taller)
{
    $resultado = ModeloCierres::mdlUltimoCierre('cierresjf');
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
                // Inicializar saldo_taller con la cantidad completa (todo disponible para taller/servicios)
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
 * Buscar ID de servicios_detallejf por artículo y taller
 * Busca primero servicios activos con saldo, luego cualquier servicio del mismo artículo/taller
 */
function buscarIdServicioDetalle($articulo, $taller, $pdo)
{
    // Primero buscar servicios_detallejf activos con saldo (preferencia)
    $stmt = $pdo->prepare("SELECT sd.id 
        FROM servicios_detallejf sd
        INNER JOIN serviciosjf s ON sd.codigo = s.codigo
        WHERE sd.articulo = :articulo 
        AND s.taller = :taller
        AND sd.cerrar = 0
        AND sd.saldo > 0
        ORDER BY sd.id ASC
        LIMIT 1");

    $stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);
    $stmt->bindParam(":taller", $taller, PDO::PARAM_STR);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if ($resultado) {
        return $resultado['id'];
    }

    // Si no encuentra activos, buscar cualquier servicio del mismo artículo/taller
    $stmt = $pdo->prepare("SELECT sd.id 
        FROM servicios_detallejf sd
        INNER JOIN serviciosjf s ON sd.codigo = s.codigo
        WHERE sd.articulo = :articulo 
        AND s.taller = :taller
        ORDER BY sd.id DESC
        LIMIT 1");

    $stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);
    $stmt->bindParam(":taller", $taller, PDO::PARAM_STR);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    return $resultado ? $resultado['id'] : null;
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
 * Crear servicios primero (para sincronizar con entaller_cabjf)
 * Luego crear cierres vinculados a esos servicios
 */
function crearServiciosYCierres($articulos, $codigoAC, $usuario)
{
    $pdo = Conexion::conectar();
    $pdo->beginTransaction();

    try {
        $guiaServicios = 'GUIA-SERVICIOS-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $guiaCierres = generarGuia();
        $registrosCreados = 0;
        $articulosConIds = []; // Array para guardar artículos con sus IDs de entaller_cabjf y servicios_detallejf

        // Primero: Registrar en entaller_cabjf y crear servicios
        mensaje("Registrando en entaller_cabjf...", 'info');
        $contadorEntaller = 0;
        foreach ($articulos as $item) {
            $articulo = trim($item['articulo']);
            $cantidad = $item['cantidad'];
            $taller = trim($item['taller']);

            // Buscar el detalle de almacén de corte que acabamos de crear
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
            $stmt->bindParam(":guia", $guiaServicios, PDO::PARAM_STR);
            $stmt->bindParam(":taller", $taller, PDO::PARAM_STR);
            $stmt->bindParam(":almacencorte_detalle_id", $detalleId, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Error al registrar artículo $articulo en taller $taller: " . print_r($errorInfo, true));
            }

            // Obtener el ID del registro recién creado en entaller_cabjf
            $entallerCabId = $pdo->lastInsertId();

            if (!$entallerCabId || $entallerCabId == 0) {
                throw new Exception("Error: No se obtuvo ID de entaller_cabjf para artículo $articulo");
            }

            $contadorEntaller++;

            if ($contadorEntaller % 50 == 0) {
                mensaje("  Procesados $contadorEntaller artículos en entaller_cabjf...", 'info');
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

            // Actualizar articulojf: disminuir taller y alm_corte
            $stmt = $pdo->prepare("UPDATE articulojf 
                SET taller = GREATEST(taller - :cantidad, 0),
                    alm_corte = GREATEST(alm_corte - :cantidad, 0)
                WHERE articulo = :articulo");
            $stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);
            $stmt->bindParam(":cantidad", $cantidadAUsar, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();

            mensaje("  Registrado en entaller_cabjf: $articulo (cantidad: $cantidadAUsar, taller: $taller)", 'info');

            // Guardar para crear servicios después
            $articulosConIds[] = [
                'articulo' => $articulo,
                'cantidad' => $cantidadAUsar,
                'taller' => $taller,
                'entaller_cab_id' => $entallerCabId
            ];
        }

        mensaje("  Total registros en entaller_cabjf: " . count($articulosConIds), 'success');
        mensaje("");

        // Segundo: Crear servicios agrupados por taller
        mensaje("Creando servicios...", 'info');
        $articulosPorTaller = [];
        foreach ($articulosConIds as $item) {
            $taller = trim($item['taller']);
            if (!isset($articulosPorTaller[$taller])) {
                $articulosPorTaller[$taller] = [];
            }
            $articulosPorTaller[$taller][] = $item;
        }

        $serviciosCreados = [];
        $mapaServiciosDetalle = []; // Mapa para vincular cierres después

        foreach ($articulosPorTaller as $taller => $itemsTaller) {
            mensaje("  Procesando taller: $taller", 'info');
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

            // Crear detalles de servicio y guardar IDs para vincular cierres
            foreach ($itemsTaller as $item) {
                $articulo = trim($item['articulo']);
                $cantidad = $item['cantidad'];
                $entallerCabId = $item['entaller_cab_id'];

                // Crear detalle de servicio con saldo = 0 porque inmediatamente pasará a cierre
                // Esto evita sumar a servicio en articulojf dos veces
                $datosDetalle = [
                    'codigo' => $codigoServicio,
                    'articulo' => $articulo,
                    'cantidad' => $cantidad,
                    'saldo' => 0, // Saldo en 0 porque inmediatamente pasa a cierre
                    'cabecera_taller' => $entallerCabId
                ];

                ModeloServicios::mdlGuardarDetallesServicios('servicios_detallejf', $datosDetalle);

                // Obtener el ID del servicio_detallejf recién creado usando lastInsertId
                $servicioDetalleId = $pdo->lastInsertId();

                // Si lastInsertId no funciona, buscar el registro recién creado
                if (!$servicioDetalleId || $servicioDetalleId == 0) {
                    $stmt = $pdo->prepare("SELECT id FROM servicios_detallejf 
                        WHERE codigo = :codigo 
                        AND articulo = :articulo 
                        ORDER BY id DESC LIMIT 1");
                    $stmt->bindParam(":codigo", $codigoServicio, PDO::PARAM_STR);
                    $stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);
                    $stmt->execute();
                    $servicioDetalle = $stmt->fetch(PDO::FETCH_ASSOC);
                    $stmt->closeCursor();

                    if ($servicioDetalle) {
                        $servicioDetalleId = $servicioDetalle['id'];
                    }
                }

                // Guardar en mapa para vincular con cierres después
                // Usar una clave única que incluya el índice para evitar sobrescritura
                $claveMapa = $articulo . '|' . $taller . '|' . $servicioDetalleId;
                $mapaServiciosDetalle[$claveMapa] = $servicioDetalleId;

                // También guardar por artículo+taller para búsqueda rápida
                if (!isset($mapaServiciosDetalle[$articulo . '|' . $taller])) {
                    $mapaServiciosDetalle[$articulo . '|' . $taller] = [];
                }
                $mapaServiciosDetalle[$articulo . '|' . $taller][] = $servicioDetalleId;

                // NO actualizar articulojf aquí porque el servicio tiene saldo = 0
                // Solo se sumará cuando se cree el cierre
            }

            $serviciosCreados[] = [
                'codigo' => $codigoServicio,
                'taller' => $taller,
                'total' => $totalTaller
            ];
            mensaje("  ✓ Servicio creado: $codigoServicio (Total: $totalTaller)", 'success');
        }
        mensaje("");

        // Tercero: Crear cierres vinculados a los servicios recién creados
        mensaje("Creando cierres...", 'info');
        $cierresCreados = [];
        foreach ($articulosPorTaller as $taller => $itemsTaller) {
            mensaje("  Procesando taller: $taller", 'info');
            // Obtener último código de cierre para este taller
            $codigoCierre = obtenerUltimoCodigoCierre($taller);

            // Calcular total para este taller
            $totalTaller = 0;
            foreach ($itemsTaller as $item) {
                $totalTaller += $item['cantidad'];
            }

            // Crear cabecera de cierre
            $fecha = date('Y-m-d H:i:s');
            $datosCierre = [
                'codigo' => $codigoCierre,
                'guia' => $guiaCierres,
                'usuario' => $usuario,
                'taller' => $taller,
                'total' => $totalTaller,
                'fecha' => $fecha,
                'estado' => 'ACTIVO'
            ];

            $resultado = ModeloCierres::mdlGuardarCierres('cierresjf', $datosCierre);

            if ($resultado !== 'ok') {
                throw new Exception("Error al crear cabecera de cierre para taller $taller");
            }

            // Crear detalles de cierre vinculados a servicios
            foreach ($itemsTaller as $item) {
                $articulo = trim($item['articulo']);
                $cantidad = $item['cantidad'];

                // Obtener ID de servicios_detallejf del mapa (recién creado)
                $codServicioId = null;

                // Buscar en el mapa por artículo+taller (puede ser array o ID directo)
                if (isset($mapaServiciosDetalle[$articulo . '|' . $taller])) {
                    $serviciosEncontrados = $mapaServiciosDetalle[$articulo . '|' . $taller];

                    // Si es un array, tomar el primero disponible
                    if (is_array($serviciosEncontrados) && !empty($serviciosEncontrados)) {
                        $codServicioId = $serviciosEncontrados[0];
                    } elseif (is_numeric($serviciosEncontrados)) {
                        // Si es un ID directo (formato antiguo)
                        $codServicioId = $serviciosEncontrados;
                    }
                }

                // Si no se encuentra en el mapa, buscar directamente por artículo y taller
                // Buscar el servicio_detallejf más reciente creado en esta transacción
                if (!$codServicioId) {
                    $stmt = $pdo->prepare("SELECT sd.id 
                        FROM servicios_detallejf sd
                        INNER JOIN serviciosjf s ON sd.codigo = s.codigo
                        WHERE sd.articulo = :articulo 
                        AND s.taller = :taller
                        AND sd.saldo = 0
                        ORDER BY sd.id DESC
                        LIMIT 1");
                    $stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);
                    $stmt->bindParam(":taller", $taller, PDO::PARAM_STR);
                    $stmt->execute();
                    $servicioEncontrado = $stmt->fetch(PDO::FETCH_ASSOC);
                    $stmt->closeCursor();

                    if ($servicioEncontrado) {
                        $codServicioId = $servicioEncontrado['id'];
                    }
                }

                // Asegurar que el saldo del servicio esté en 0 (ya se creó con saldo = 0)
                // y obtener información para actualizar entaller_cabjf
                if ($codServicioId) {
                    // Obtener información del servicio para actualizar también entaller_cabjf
                    $stmt = $pdo->prepare("SELECT saldo, cantidad, cabecera_taller FROM servicios_detallejf WHERE id = :id");
                    $stmt->bindParam(":id", $codServicioId, PDO::PARAM_INT);
                    $stmt->execute();
                    $servicioDetalle = $stmt->fetch(PDO::FETCH_ASSOC);
                    $stmt->closeCursor();

                    if ($servicioDetalle) {
                        // Asegurar que el saldo esté en 0 (por si acaso)
                        $stmt = $pdo->prepare("UPDATE servicios_detallejf 
                            SET saldo = 0
                            WHERE id = :id");
                        $stmt->bindParam(":id", $codServicioId, PDO::PARAM_INT);
                        $stmt->execute();
                        $stmt->closeCursor();

                        // También descontar de entaller_cabjf si está vinculado
                        if ($servicioDetalle['cabecera_taller']) {
                            $stmt = $pdo->prepare("UPDATE entaller_cabjf 
                                SET saldo = GREATEST(saldo - :cantidad, 0)
                                WHERE id = :id_cabecera AND estado = '0'");
                            $stmt->bindParam(":cantidad", $cantidad, PDO::PARAM_INT);
                            $stmt->bindParam(":id_cabecera", $servicioDetalle['cabecera_taller'], PDO::PARAM_INT);
                            $stmt->execute();
                            $stmt->closeCursor();

                            // Si el saldo llegó a cero, cerrar el registro en entaller_cabjf
                            $stmt = $pdo->prepare("SELECT saldo FROM entaller_cabjf WHERE id = :id_cabecera");
                            $stmt->bindParam(":id_cabecera", $servicioDetalle['cabecera_taller'], PDO::PARAM_INT);
                            $stmt->execute();
                            $cabecera = $stmt->fetch(PDO::FETCH_ASSOC);
                            $stmt->closeCursor();

                            if ($cabecera && $cabecera['saldo'] <= 0) {
                                $stmt = $pdo->prepare("UPDATE entaller_cabjf 
                                    SET estado = 1 
                                    WHERE id = :id_cabecera");
                                $stmt->bindParam(":id_cabecera", $servicioDetalle['cabecera_taller'], PDO::PARAM_INT);
                                $stmt->execute();
                                $stmt->closeCursor();
                            }
                        }
                    }
                } else {
                    // Advertencia si no se encontró el servicio para descontar
                    mensaje("  Advertencia: No se encontró servicios_detallejf para artículo $articulo en taller $taller para descontar saldo", 'warning');
                }

                // Crear detalle de cierre
                $datosDetalle = [
                    'codigo' => $codigoCierre,
                    'articulo' => $articulo,
                    'cantidad' => $cantidad,
                    'inicio' => $cantidad,
                    'cod_servicio' => $codServicioId ? strval($codServicioId) : null
                ];

                ModeloCierres::mdlGuardarDetallesCierres('cierres_detallejf', $datosDetalle);

                // Actualizar articulojf: sumar a servicio solo cuando se crea el cierre
                // NOTA: Los servicios se crean con saldo = 0 y NO se suma a servicio en articulojf
                // Solo se suma cuando se crea el cierre (servicio = servicios + cierres)
                // Como los servicios tienen saldo = 0, solo contamos los cierres
                $stmt = $pdo->prepare("UPDATE articulojf 
                    SET servicio = servicio + :cantidad
                    WHERE articulo = :articulo");
                $stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);
                $stmt->bindParam(":cantidad", $cantidad, PDO::PARAM_INT);
                $stmt->execute();
                $stmt->closeCursor();

                $registrosCreados++;
            }

            $cierresCreados[] = [
                'codigo' => $codigoCierre,
                'taller' => $taller,
                'total' => $totalTaller
            ];
            mensaje("  ✓ Cierre creado: $codigoCierre (Total: $totalTaller)", 'success');
        }
        mensaje("");

        mensaje("Confirmando transacción...", 'info');
        $pdo->commit();
        mensaje("✓ Transacción confirmada", 'success');

        // Actualizar servicio total (suma servicios + cierres) - FUERA de la transacción para evitar bloqueos
        mensaje("Actualizando servicio total...", 'info');
        try {
            ModeloCierres::mdlActualizarServicioTotal();
            mensaje("✓ Servicio total actualizado", 'success');
        } catch (Exception $e) {
            mensaje("Advertencia: Error al actualizar servicio total: " . $e->getMessage(), 'warning');
            // Continuar aunque falle la actualización del servicio total
        }
        return [
            'registros' => $registrosCreados,
            'guia_servicios' => $guiaServicios,
            'guia_cierres' => $guiaCierres,
            'servicios' => $serviciosCreados,
            'cierres' => $cierresCreados
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
        mensaje("PROCESADOR DE INVENTARIO - CIERRES", 'info');
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

        // Crear servicios y cierres (servicios primero para sincronizar con entaller_cabjf)
        mensaje("Creando Servicios y Cierres...", 'info');
        $resultado = crearServiciosYCierres($articulos, $nuevoAC, USUARIO_ID);
        mensaje("✓ Registrados {$resultado['registros']} registros", 'success');
        mensaje("  Guía de servicios: {$resultado['guia_servicios']}", 'info');
        mensaje("  Guía de cierres: {$resultado['guia_cierres']}", 'info');

        // Mostrar servicios creados
        mensaje("✓ Servicios creados:", 'success');
        foreach ($resultado['servicios'] as $servicio) {
            mensaje("  - {$servicio['codigo']} (Taller: {$servicio['taller']}, Total: {$servicio['total']})", 'info');
        }

        // Mostrar cierres creados
        mensaje("✓ Cierres creados:", 'success');
        foreach ($resultado['cierres'] as $cierre) {
            mensaje("  - {$cierre['codigo']} (Taller: {$cierre['taller']}, Total: {$cierre['total']})", 'info');
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
        mensaje("  - Guía Servicios: {$resultado['guia_servicios']}", 'info');
        mensaje("  - Guía Cierres: {$resultado['guia_cierres']}", 'info');
        mensaje("  - Artículos únicos: " . count($articulosUnicos), 'info');
        mensaje("  - Talleres únicos: " . count($talleresUnicos), 'info');
        mensaje("  - Total unidades: " . $totalUnidades, 'info');
        mensaje("  - Registros procesados: {$resultado['registros']}", 'info');
        mensaje("  - Servicios creados: " . count($resultado['servicios']), 'info');
        mensaje("  - Cierres creados: " . count($resultado['cierres']), 'info');
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
