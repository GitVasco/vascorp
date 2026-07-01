<?php

class ControladorInteligenciaComercial
{
    public static function ctrClientesAnalisis()
    {
        return ModeloInteligenciaComercial::mdlClientesAnalisis();
    }

    public static function ctrCalcularMotorRiesgoCredito($codigoCliente)
    {
        $codigoCliente = trim((string) $codigoCliente);

        if ($codigoCliente === "") {
            return null;
        }

        return ModeloInteligenciaComercial::mdlCalcularMotorRiesgoCredito($codigoCliente);
    }

    public static function ctrCalcularMotorComercial($codigoCliente)
    {
        $codigoCliente = trim((string) $codigoCliente);

        if ($codigoCliente === "") {
            return null;
        }

        return ModeloInteligenciaComercial::mdlCalcularMotorComercial($codigoCliente);
    }
}
