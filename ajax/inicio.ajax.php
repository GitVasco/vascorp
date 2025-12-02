<?php

date_default_timezone_set('America/Lima');

require_once '../controladores/movimientos.controlador.php';
require_once '../modelos/movimientos.modelo.php';
require_once '../controladores/articulos.controlador.php';
require_once '../modelos/articulos.modelo.php';

class AjaxInicio
{

    public $mes;

    public function ajaxObtenerDatosCajas()
    {

        $mes = $this->mes;
        $año = isset($_POST["año"]) ? $_POST["año"] : null;

        // Si mes es null o "null", obtener datos del mes actual
        if ($mes == null || $mes == "null" || $mes == "" || $mes == "0") {
            // Usar mes actual
            $añoConsulta = date('Y');
            $mesConsulta = date('n');
        } else {
            // Si no se proporciona año, usar el año actual
            if ($año == null || $año == "") {
                $añoConsulta = date('Y');
            } else {
                $añoConsulta = intval($año);
            }
            $mesConsulta = intval($mes);
        }

        // Usar los nuevos métodos que aceptan año y mes específicos
        $ventas = ControladorMovimientos::ctrTotUndVenMesEspecifico($añoConsulta, $mesConsulta);
        $produccion = ControladorMovimientos::ctrTotUndProdMesEspecifico($añoConsulta, $mesConsulta);
        $cortes = ControladorMovimientos::ctrTotUndCorteMesEspecifico($añoConsulta, $mesConsulta);
        $articulosP = controladorArticulos::ctrArticulosPedidos();
        $articulosF = controladorArticulos::ctrArticulosFaltantes();

        if ($articulosF["faltantes"] == '0' || $articulosP["pedidos"] == '0') {
            $porcentaje = 0;
        } else {
            $porcentaje = number_format($articulosF["faltantes"] * 100 / $articulosP["pedidos"], 2);
        }

        $respuesta = array(
            "ventas" => number_format($ventas["total_venta"], 0),
            "produccion" => number_format($produccion["total_produccion"], 0),
            "cortes" => number_format($cortes["total_corte"], 0),
            "pedidos" => number_format($articulosP["pedidos"], 0),
            "faltantes" => number_format($articulosF["faltantes"], 0),
            "porcentaje" => $porcentaje
        );

        echo json_encode($respuesta);
    }
}

if (isset($_POST["mes"])) {
    $obtenerDatos = new AjaxInicio();
    $obtenerDatos->mes = $_POST["mes"];
    $obtenerDatos->ajaxObtenerDatosCajas();
}
