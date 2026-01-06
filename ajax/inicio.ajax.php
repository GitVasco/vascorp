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

    public function ajaxObtenerDatosGraficoCorteProd()
    {
        $mes = isset($_POST["mes"]) ? $_POST["mes"] : null;
        $año = isset($_POST["año"]) ? $_POST["año"] : null;

        // Si mes es null o "null", obtener datos del año actual
        if ($mes == null || $mes == "null" || $mes == "" || $mes == "0") {
            $añoConsulta = date('Y');
        } else {
            // Si no se proporciona año, usar el año actual
            if ($año == null || $año == "") {
                $añoConsulta = date('Y');
            } else {
                $añoConsulta = intval($año);
            }
        }

        // Obtener datos del año seleccionado
        $meses = ControladorMovimientos::ctrMesesMovPorAño($añoConsulta);
        $produccion = ControladorMovimientos::ctrTotalMesProdPorAño($añoConsulta);
        $cortes = ControladorMovimientos::ctrTotalMesCortePorAño($añoConsulta);

        // Preparar arrays para el gráfico
        $arrayMeses = array();
        $arrayProduccion = array();
        $arrayCorte = array();

        foreach ($meses as $mesData) {
            $arrayMeses[] = $mesData['nom_mes'];
        }

        foreach ($produccion as $prodData) {
            $arrayProduccion[] = floatval($prodData['total_mesP']);
        }

        foreach ($cortes as $corteData) {
            $arrayCorte[] = floatval($corteData['total_mesC']);
        }

        $respuesta = array(
            "meses" => $arrayMeses,
            "produccion" => $arrayProduccion,
            "corte" => $arrayCorte
        );

        echo json_encode($respuesta);
    }

    public function ajaxObtenerDatosGraficoProdTaller()
    {
        $mes = isset($_POST["mes"]) ? $_POST["mes"] : null;
        $año = isset($_POST["año"]) ? $_POST["año"] : null;

        // Si mes es null o "null", obtener datos del año actual
        if ($mes == null || $mes == "null" || $mes == "" || $mes == "0") {
            $añoConsulta = date('Y');
        } else {
            // Si no se proporciona año, usar el año actual
            if ($año == null || $año == "") {
                $añoConsulta = date('Y');
            } else {
                $añoConsulta = intval($año);
            }
        }

        // Obtener meses del año
        $meses = ControladorMovimientos::ctrMesesMovPorAño($añoConsulta);
        
        // Obtener producción por taller del año seleccionado
        $produccion_taller = ControladorMovimientos::ctrTotalMesProdTallerPorAño($añoConsulta, null);

        // Preparar arrays
        $arrayMeses = array();
        foreach ($meses as $mesData) {
            $arrayMeses[] = $mesData['nom_mes'];
        }

        // Organizar datos por taller
        $arrayProduccion = array();
        $arrayTalleres = array();

        foreach ($produccion_taller as $value) {
            $mes = intval($value['mes']) - 1; // restamos 1 porque los meses en PHP van de 1 a 12 y en JavaScript de 0 a 11
            $taller = $value['taller'];
            $produccion = floatval($value['produccion']);

            if (!isset($arrayProduccion[$taller])) {
                // Crear array indexado numéricamente (0-11) para los 12 meses
                $arrayProduccion[$taller] = array();
                for ($i = 0; $i < 12; $i++) {
                    $arrayProduccion[$taller][$i] = 0;
                }
            }
            
            // Asegurar que el índice del mes esté en el rango válido (0-11)
            if ($mes >= 0 && $mes < 12) {
                $arrayProduccion[$taller][$mes] = $produccion;
            }
            
            $arrayTalleres[$taller] = $value['nom_sector'];
        }

        // Convertir arrays asociativos a arrays indexados numéricamente para JSON
        // Esto asegura que se conviertan correctamente a arrays JavaScript
        $produccionFormateada = array();
        foreach ($arrayProduccion as $taller => $datos) {
            // Asegurar que sea un array indexado numéricamente
            $produccionFormateada[$taller] = array_values($datos);
        }

        $respuesta = array(
            "meses" => $arrayMeses,
            "produccion" => $produccionFormateada,
            "talleres" => $arrayTalleres
        );

        echo json_encode($respuesta, JSON_NUMERIC_CHECK);
    }

    public function ajaxObtenerDatosGraficoVtasProd()
    {
        $mes = isset($_POST["mes"]) ? $_POST["mes"] : null;
        $año = isset($_POST["año"]) ? $_POST["año"] : null;

        // Si mes es null o "null", obtener datos del año actual
        if ($mes == null || $mes == "null" || $mes == "" || $mes == "0") {
            $añoConsulta = date('Y');
        } else {
            // Si no se proporciona año, usar el año actual
            if ($año == null || $año == "") {
                $añoConsulta = date('Y');
            } else {
                $añoConsulta = intval($año);
            }
        }

        // Obtener datos del año seleccionado
        $meses = ControladorMovimientos::ctrMesesMovPorAño($añoConsulta);
        $ventas = ControladorMovimientos::ctrTotalMesVentPorAño($añoConsulta);
        $produccion = ControladorMovimientos::ctrTotalMesProdPorAño($añoConsulta);

        // Preparar arrays para el gráfico
        $arrayMeses = array();
        $arrayVentas = array();
        $arrayProduccion = array();

        foreach ($meses as $mesData) {
            $arrayMeses[] = $mesData['nom_mes'];
        }

        foreach ($ventas as $ventaData) {
            $arrayVentas[] = floatval($ventaData['total_mesV']);
        }

        foreach ($produccion as $prodData) {
            $arrayProduccion[] = floatval($prodData['total_mesP']);
        }

        $respuesta = array(
            "meses" => $arrayMeses,
            "ventas" => $arrayVentas,
            "produccion" => $arrayProduccion
        );

        echo json_encode($respuesta);
    }
}

if (isset($_POST["mes"])) {
    $obtenerDatos = new AjaxInicio();
    $obtenerDatos->mes = $_POST["mes"];
    
    if (isset($_POST["accion"]) && $_POST["accion"] == "graficoCorteProd") {
        $obtenerDatos->ajaxObtenerDatosGraficoCorteProd();
    } else if (isset($_POST["accion"]) && $_POST["accion"] == "graficoProdTaller") {
        $obtenerDatos->ajaxObtenerDatosGraficoProdTaller();
    } else if (isset($_POST["accion"]) && $_POST["accion"] == "graficoVtasProd") {
        $obtenerDatos->ajaxObtenerDatosGraficoVtasProd();
    } else {
        $obtenerDatos->ajaxObtenerDatosCajas();
    }
}
