<?php

/**
 * Carga los prompts del resumen ejecutivo (IA) desde archivo de texto.
 *
 * Editar: controladores/prompts/inteligencia-comercial/resumen-ia.prompt.txt
 * Ejemplo JSON enviado: controladores/prompts/inteligencia-comercial/resumen-ia-contexto-ejemplo.json
 * Ejemplo respuesta ideal: controladores/prompts/inteligencia-comercial/resumen-ia-respuesta-ejemplo.json
 * Secciones: ===== SYSTEM ===== y ===== USER =====
 * Placeholder en USER: {{CONTEXTO_JSON}}
 */

function icPromptResumenIaRutaArchivo()
{
    return __DIR__ . "/prompts/inteligencia-comercial/resumen-ia.prompt.txt";
}

/**
 * @return array{system: string, user: string}
 */
function icPromptResumenIaCargar()
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $ruta = icPromptResumenIaRutaArchivo();

    if (!is_readable($ruta)) {
        $cache = array(
            "system" => "",
            "user"   => "Analiza este cliente y genera resumen + recomendaciones:\n\n{{CONTEXTO_JSON}}",
        );

        return $cache;
    }

    $contenido = file_get_contents($ruta);
    $system = "";
    $user = "";

    if (preg_match('/===== SYSTEM =====\s*(.*?)\s*===== USER =====\s*(.*)\z/s', $contenido, $coincidencias)) {
        $system = trim($coincidencias[1]);
        $user = trim($coincidencias[2]);
    }

    $cache = array(
        "system" => $system,
        "user"   => $user,
    );

    return $cache;
}

function icPromptResumenIaSistema()
{
    $prompts = icPromptResumenIaCargar();

    return $prompts["system"];
}

function icPromptResumenIaUsuario($contextoJson)
{
    $prompts = icPromptResumenIaCargar();
    $plantilla = $prompts["user"];

    if ($plantilla === "") {
        $plantilla = "Analiza este cliente y genera resumen + recomendaciones:\n\n{{CONTEXTO_JSON}}";
    }

    return str_replace("{{CONTEXTO_JSON}}", $contextoJson, $plantilla);
}
