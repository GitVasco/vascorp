<?php

require_once "controladores/cuentas.controlador.php";
require_once "modelos/cuentas.modelo.php";
require_once "controladores/clientes.controlador.php";
require_once "modelos/clientes.modelo.php";
require_once "controladores/config.php";

// Definir el nombre de la empresa
$nombreEmpresa = "CORPORACION VASCO SAC";
$instancia = "NTE5NDU0NzA3Mzg=";
$entorno = "produccion"; // Cambiar a "produccion" para el entorno real
$numeroPrueba = NUMERO_PRUEBAS; // Número de teléfono para pruebas
$token = TOKEN_WHATSAPP; // Token de WhatsApp

// Obtener cuentas con notificaciones pendientes
$cuentas = ControladorCuentas::ctrNotificacionesPendientes();
$cuentasHoy = [];

// Filtrar cuentas que tienen 'num_unico', 'telefono' y 'tipo_notificacion' no vacíos
foreach ($cuentas as $value) {
    if (
        !empty($value["num_unico"]) &&
        !empty($value["telefono"]) &&
        !empty($value["tipo_notificacion"])
    ) {
        $cuentasHoy[] = $value;
    }
}

// Preparar el array para la API y el resumen
$mensajes = [];
$resumen = [
    'total_mensajes' => 0,
    'vencimiento'    => 0,
    'recordatorio'   => 0,
    'documentos'     => [] // Nueva llave para la relación de documentos
];
$registroNotificaciones = []; // Para el archivo de registro

// Obtener la fecha actual
$fechaActual = date("Y-m-d");


foreach ($cuentasHoy as $index => $cuenta) {
    // Si el entorno es "pruebas" y no es el primer recorrido, salir del bucle
    if ($entorno === "pruebas" && $index > 0) {
        break;
    }

    // Determinar el tipo de notificación y generar el mensaje correspondiente
    if ($cuenta['tipo_notificacion'] === 'VENCIMIENTO') {
        $mensaje = "Hola {$cuenta['nombre']} 👋\n"
            . "¡Tu letra vence hoy!\n"
            . "🆔 Nro único: {$cuenta['num_unico']}\n"
            . "💰 Monto: S/{$cuenta['monto']}\n"
            . "📅 Fecha vencimiento: {$cuenta['fecha_ven']}\n"
            . "Por favor, realiza su cancelación desde la ventanilla de cualquier agencia BCP o desde su web.\n"
            . "Gracias.\n{$nombreEmpresa}\nJACKYFORM-ROSALINDA-VASCO";
        $resumen['vencimiento'] += 1;
    } elseif ($cuenta['tipo_notificacion'] === 'RECORDATORIO') {
        $mensaje = "Hola {$cuenta['nombre']} 👋\n"
            . "Este mensaje es para recordarte que han pasado 5 días desde el vencimiento de tu letra.\n"
            . "🆔 Nro único: {$cuenta['num_unico']}\n"
            . "💰 Monto: S/{$cuenta['monto']}\n"
            . "📅 Fecha vencimiento: {$cuenta['fecha_ven']}\n"
            . "Por favor, realiza su cancelación desde la ventanilla de cualquier agencia BCP o desde su web.\n"
            . "Gracias.\n{$nombreEmpresa}\nJACKYFORM-ROSALINDA-VASCO";
        $resumen['recordatorio'] += 1;
    } else {
        // Si hay otro tipo de notificación, se puede manejar aquí
        continue; // Saltar si el tipo de notificación no es reconocido
    }

    $telefono = $entorno === "pruebas" ? $numeroPrueba : "51" . $cuenta['telefono'];

    $mensajes[] = [
        'telefono' => $telefono,
        'mensaje'  => $mensaje,
        'tipo'     => $cuenta['tipo_notificacion']
    ];

    $contenido = [
        "number" => $telefono,
        "text" => $mensaje,
    ];

    $response = ControladorClientes::ctrEnviarNotificaciones($instancia, json_encode($contenido), $token);
    $responseDecoded = json_decode($response, true);

    $exito = isset($responseDecoded['status']) && $responseDecoded['status'] === 200;

    // Incrementar el contador total de mensajes
    $resumen['total_mensajes'] += 1;

    // Agregar los detalles del documento al resumen
    $resumen['documentos'][] = [
        'num_cta'        => $cuenta['num_cta'],
        'fecha_ven'      => $cuenta['fecha_ven'],
        'saldo'          => $cuenta['saldo'],
        'num_unico'      => $cuenta['num_unico'],
        'nombre'         => $cuenta['nombre'],
        'telefono'       => $telefono,
        'tipo_notificacion' => $cuenta['tipo_notificacion'],
        'exito'          => $exito
    ];

    // Agregar la notificación al registro de notificaciones
    $registroNotificaciones[] = [
        'telefono' => $telefono,
        'mensaje'  => $mensaje,
        'tipo'     => $cuenta['tipo_notificacion'],
        'fecha_envio' => $fechaActual,
        'exito'       => $exito
    ];

    // Pausar 5 segundos antes de enviar el siguiente mensaje
    sleep(5);
}

// Opcional: Guardar los mensajes en un archivo TXT para pruebas
file_put_contents('mensajes.txt', print_r($mensajes, true));

// ----------- Generación del JSON de Resumen por Fecha ----------- //

// Ruta del archivo de resumen
$rutaResumen = 'resumen_envios.json';

// Verificar si el archivo de resumen existe
if (file_exists($rutaResumen)) {
    // Leer el contenido existente
    $contenidoResumen = file_get_contents($rutaResumen);
    $dataResumen = json_decode($contenidoResumen, true);
    if ($dataResumen === null) {
        // Manejar error de decodificación
        $dataResumen = [];
    }
} else {
    // Inicializar un array vacío si el archivo no existe
    $dataResumen = [];
}

// Agregar el resumen de hoy
$dataResumen[$fechaActual] = $resumen;

// Guardar el resumen actualizado
file_put_contents($rutaResumen, json_encode($dataResumen, JSON_PRETTY_PRINT));

// ----------- Generación del Registro de Notificaciones ----------- //

// Ruta del archivo de registro
$rutaRegistro = 'registro_notificaciones.json';

// Verificar si el archivo de registro existe
if (file_exists($rutaRegistro)) {
    // Leer el contenido existente
    $contenidoRegistro = file_get_contents($rutaRegistro);
    $dataRegistro = json_decode($contenidoRegistro, true);
    if ($dataRegistro === null) {
        // Manejar error de decodificación
        $dataRegistro = [];
    }
} else {
    // Inicializar un array vacío si el archivo no existe
    $dataRegistro = [];
}

// Agregar las notificaciones de hoy
if (!isset($dataRegistro[$fechaActual])) {
    $dataRegistro[$fechaActual] = [];
}
$dataRegistro[$fechaActual] = array_merge($dataRegistro[$fechaActual], $registroNotificaciones);

// Guardar el registro actualizado
file_put_contents($rutaRegistro, json_encode($dataRegistro, JSON_PRETTY_PRINT));

// ----------- Opcional: Imprimir el JSON de Resumen en Pantalla ----------- //
// header('Content-Type: application/json');
// echo $jsonResumen;
