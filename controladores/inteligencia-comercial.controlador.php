<?php

class ControladorInteligenciaComercial
{
    public static function ctrClientesAnalisis()
    {
        return ModeloInteligenciaComercial::mdlClientesAnalisis();
    }

    public static function ctrCalcularMotorRiesgoCredito($codigoCliente, $lineaRecomendadaMotor3 = null)
    {
        $codigoCliente = trim((string) $codigoCliente);

        if ($codigoCliente === "") {
            return null;
        }

        return ModeloInteligenciaComercial::mdlCalcularMotorRiesgoCredito(
            $codigoCliente,
            $lineaRecomendadaMotor3
        );
    }

    /**
     * Calcula todos los motores con dos pasadas: Motor 3 define la línea recomendada
     * y el Motor 1 la usa para el factor de utilización.
     */
    public static function ctrCalcularAnalisisCompleto($codigoCliente)
    {
        $codigoCliente = trim((string) $codigoCliente);

        if ($codigoCliente === "") {
            return array(
                "motor1" => null,
                "motor2" => null,
                "motor3" => null,
                "motor4" => null,
            );
        }

        $motor1 = self::ctrCalcularMotorRiesgoCredito($codigoCliente);
        $motor2 = self::ctrCalcularMotorComercial($codigoCliente);
        $motor4 = self::ctrCalcularMotorFidelidad($codigoCliente);
        $motor3 = self::ctrCalcularMotorLineaCredito($codigoCliente, $motor1, $motor2, $motor4);

        if ($motor3) {
            $lineaRecomendada = (float) $motor3["linea"]["linea_recomendada"];

            if ($lineaRecomendada > 0) {
                $motor1 = self::ctrCalcularMotorRiesgoCredito($codigoCliente, $lineaRecomendada);
                $motor3 = self::ctrCalcularMotorLineaCredito($codigoCliente, $motor1, $motor2, $motor4);
            }
        }

        return array(
            "motor1" => $motor1,
            "motor2" => $motor2,
            "motor3" => $motor3,
            "motor4" => $motor4,
        );
    }

    public static function ctrCalcularMotorComercial($codigoCliente)
    {
        $codigoCliente = trim((string) $codigoCliente);

        if ($codigoCliente === "") {
            return null;
        }

        return ModeloInteligenciaComercial::mdlCalcularMotorComercial($codigoCliente);
    }

    public static function ctrCalcularMotorFidelidad($codigoCliente)
    {
        $codigoCliente = trim((string) $codigoCliente);

        if ($codigoCliente === "") {
            return null;
        }

        return ModeloInteligenciaComercial::mdlCalcularMotorFidelidad($codigoCliente);
    }

    public static function ctrCalcularMotorLineaCredito(
        $codigoCliente,
        $resultadoMotor1 = null,
        $resultadoMotor2 = null,
        $resultadoMotor4 = null
    ) {
        $codigoCliente = trim((string) $codigoCliente);

        if ($codigoCliente === "") {
            return null;
        }

        return ModeloInteligenciaComercial::mdlCalcularMotorLineaCredito(
            $codigoCliente,
            $resultadoMotor1,
            $resultadoMotor2,
            $resultadoMotor4
        );
    }

    /**
     * Resumen narrativo del cliente vía OpenAI (no modifica scores).
     */
    public static function ctrGenerarResumenIa($codigoCliente)
    {
        $codigoCliente = trim((string) $codigoCliente);

        if ($codigoCliente === "") {
            return array("ok" => false, "msg" => "Seleccione un cliente.");
        }

        $cfg = icConfigOpenAi();

        if (empty($cfg["activo"])) {
            return array("ok" => false, "msg" => "El resumen con IA está desactivado.");
        }

        if ($cfg["api_key"] === "") {
            return array(
                "ok"  => false,
                "msg" => "Configure su clave en OPENAI_API_KEY (controladores/config.php).",
            );
        }

        $analisis = self::ctrCalcularAnalisisCompleto($codigoCliente);

        if (empty($analisis["motor1"])) {
            return array("ok" => false, "msg" => "No hay análisis disponible para este cliente.");
        }

        $contexto = icConstruirContextoResumenIa($analisis);
        $resultado = icOpenAiGenerarResumenCliente($contexto);

        if (empty($resultado["ok"])) {
            return $resultado;
        }

        return array(
            "ok"                    => true,
            "cliente"               => $contexto["cliente"],
            "resumen"               => $resultado["resumen"],
            "decision"              => $resultado["decision"],
            "que_significa"         => $resultado["que_significa"],
            "linea_credito"         => $resultado["linea_credito"],
            "como_mejorar"          => $resultado["como_mejorar"],
            "alertas"               => $resultado["alertas"],
            "linea_credito_por_que" => $resultado["linea_credito_por_que"],
            "recomendaciones"       => $resultado["recomendaciones"],
            "modelo"                => isset($resultado["modelo"]) ? $resultado["modelo"] : "",
            "modelo_respaldo"       => !empty($resultado["modelo_respaldo"]),
            "generado_en"           => date("Y-m-d H:i:s"),
        );
    }
}
