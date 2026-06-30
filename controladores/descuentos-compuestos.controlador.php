<?php

class ControladorDescuentosCompuestos
{
    /**
     * Lista descuentos compuestos ESSO (vista consolidada).
     */
    public static function ctrListarDescuentosCompuestos($origenNota = "", $limite = 2000, $cliente = "")
    {
        $origenNota = trim((string) $origenNota);
        $cliente = trim((string) $cliente);

        if (!in_array($origenNota, array("AUTO", "MANUAL", "REVISAR", "DESCARTADO"), true)) {
            $origenNota = "";
        }

        return ModeloDescuentosCompuestos::mdlListarDescuentosCompuestos($origenNota, $limite, $cliente);
    }

    /**
     * Lista de clientes con descuentos compuestos (para el filtro).
     */
    public static function ctrClientesConDescuentos()
    {
        return ModeloDescuentosCompuestos::mdlClientesConDescuentos();
    }

    /**
     * Resumen agregado por cliente (para exportación).
     */
    public static function ctrResumenPorCliente()
    {
        return ModeloDescuentosCompuestos::mdlResumenPorCliente();
    }

    /**
     * Registro individual (para el modal de edición).
     */
    public static function ctrObtenerDescuentoCompuesto($id)
    {
        return ModeloDescuentosCompuestos::mdlObtenerDescuentoCompuesto($id);
    }

    /**
     * Resumen por origen (AUTO / MANUAL / REVISAR) para las cajas superiores.
     */
    public static function ctrResumenDescuentosCompuestos($cliente = "")
    {
        $filas = ModeloDescuentosCompuestos::mdlResumenDescuentosCompuestos($cliente);

        $resumen = array(
            "AUTO" => array("total" => 0, "monto_total" => 0, "monto_pct1" => 0, "monto_pct2" => 0),
            "MANUAL" => array("total" => 0, "monto_total" => 0, "monto_pct1" => 0, "monto_pct2" => 0),
            "REVISAR" => array("total" => 0, "monto_total" => 0, "monto_pct1" => 0, "monto_pct2" => 0),
            "DESCARTADO" => array("total" => 0, "monto_total" => 0, "monto_pct1" => 0, "monto_pct2" => 0),
        );

        $totalGeneral = 0;
        $montoGeneral = 0;

        foreach ($filas as $fila) {
            $origen = (string) $fila["origen_nota"];

            if (!isset($resumen[$origen])) {
                continue;
            }

            $resumen[$origen]["total"] = (int) $fila["total"];
            $resumen[$origen]["monto_total"] = (float) $fila["monto_total"];
            $resumen[$origen]["monto_pct1"] = (float) $fila["monto_pct1_total"];
            $resumen[$origen]["monto_pct2"] = (float) $fila["monto_pct2_total"];

            // Los descartados no cuentan en los totales de trabajo.
            if ($origen !== "DESCARTADO") {
                $totalGeneral += (int) $fila["total"];
                $montoGeneral += (float) $fila["monto_total"];
            }
        }

        // Calculados = registros con propuesta (AUTO) o aceptados (MANUAL).
        // "monto adicional" = segundo descuento (pct2) sobre la diferencia.
        $calculadosBase = $resumen["AUTO"]["monto_pct1"] + $resumen["MANUAL"]["monto_pct1"];
        $calculadosAdicional = $resumen["AUTO"]["monto_pct2"] + $resumen["MANUAL"]["monto_pct2"];

        $resumen["TOTAL"] = array(
            "total" => $totalGeneral,
            "monto_total" => $montoGeneral,
            "monto_pct1" => $calculadosBase,
            "monto_pct2" => $calculadosAdicional,
        );

        $resumen["CALCULADOS"] = array(
            "base" => $calculadosBase,
            "adicional" => $calculadosAdicional,
        );

        return $resumen;
    }

    /**
     * Guarda o actualiza una corrección manual.
     */
    public static function ctrGuardarCorreccion($datos)
    {
        if (!isset($datos["id"]) || !isset($datos["nota_estandar"])) {
            return "Datos incompletos.";
        }

        $usuario = isset($_SESSION["nombre"]) ? $_SESSION["nombre"] : (isset($_SESSION["usuario"]) ? $_SESSION["usuario"] : null);

        return ModeloDescuentosCompuestos::mdlGuardarCorreccion(array(
            "id" => $datos["id"],
            "nota_estandar" => $datos["nota_estandar"],
            "observacion" => isset($datos["observacion"]) ? $datos["observacion"] : "",
            "estado" => isset($datos["estado"]) ? $datos["estado"] : "CONFIRMADO",
            "usureg" => $usuario,
            "pcreg" => isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : null,
        ));
    }

    /**
     * Confirma la propuesta automática de un registro.
     */
    public static function ctrConfirmarPropuesta($id)
    {
        $usuario = isset($_SESSION["nombre"]) ? $_SESSION["nombre"] : (isset($_SESSION["usuario"]) ? $_SESSION["usuario"] : null);
        $pc = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : null;

        return ModeloDescuentosCompuestos::mdlConfirmarPropuesta($id, $usuario, $pc);
    }

    /**
     * Descarta un registro (deja de listarse).
     */
    public static function ctrDescartar($id)
    {
        $usuario = isset($_SESSION["nombre"]) ? $_SESSION["nombre"] : (isset($_SESSION["usuario"]) ? $_SESSION["usuario"] : null);
        $pc = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : null;

        return ModeloDescuentosCompuestos::mdlDescartar($id, $usuario, $pc);
    }

    /**
     * Restaura un registro descartado.
     */
    public static function ctrRestaurar($id)
    {
        return ModeloDescuentosCompuestos::mdlRestaurar($id);
    }
}
