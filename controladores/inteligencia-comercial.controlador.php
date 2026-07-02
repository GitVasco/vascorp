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
        $motor3 = self::ctrCalcularMotorLineaCredito($codigoCliente, $motor1, $motor2);

        if ($motor3) {
            $lineaRecomendada = (float) $motor3["linea"]["linea_recomendada"];

            if ($lineaRecomendada > 0) {
                $motor1 = self::ctrCalcularMotorRiesgoCredito($codigoCliente, $lineaRecomendada);
                $motor3 = self::ctrCalcularMotorLineaCredito($codigoCliente, $motor1, $motor2);
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

    public static function ctrCalcularMotorLineaCredito($codigoCliente, $resultadoMotor1 = null, $resultadoMotor2 = null)
    {
        $codigoCliente = trim((string) $codigoCliente);

        if ($codigoCliente === "") {
            return null;
        }

        return ModeloInteligenciaComercial::mdlCalcularMotorLineaCredito(
            $codigoCliente,
            $resultadoMotor1,
            $resultadoMotor2
        );
    }
}
