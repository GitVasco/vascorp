<?php

require_once "../../controladores/articulos.controlador.php";
require_once "../../modelos/articulos.modelo.php";

class TablaUrgencias
{

    /*=============================================
    MOSTRAR LA TABLA DE URGENCIAS
    =============================================*/

    public function mostrarUrgencias()
    {

        $filtroReceta = isset($_GET["filtroReceta"]) ? $_GET["filtroReceta"] : "";
        $esSeguimientoRecetas = ($filtroReceta === "1");
        $mpFiltroReceta = "";
        if ($esSeguimientoRecetas) {
            $sublinea = isset($_GET["sublinea"]) ? $_GET["sublinea"] : "";
            $mp = isset($_GET["mp"]) ? $_GET["mp"] : "";
            $mpFiltroReceta = trim((string) $mp);
            $articulos = controladorArticulos::ctrMostrarSeguimientoPorReceta($sublinea, $mp);
        } else {
            $valor = isset($_GET["articuloUrgencia"]) ? $_GET["articuloUrgencia"] : "null";
            $articulos = controladorArticulos::ctrMostrarSeguimiento($valor);
        }
        if (count($articulos) > 0) {

            #var_dump("articulos", $articulos);

            $datosJson = '{
        "data": [';
            $filasSeguimientoRecetas = array();
            $totalesSegReceta = null;
            if ($esSeguimientoRecetas) {
                $totalesSegReceta = array(
                    "stock" => 0,
                    "pedidos" => 0,
                    "taller" => 0,
                    "servicio" => 0,
                    "arreglos" => 0,
                    "alm_corte" => 0,
                    "ord_corte" => 0,
                    "consumo_ord_corte" => 0,
                );
            }

            for ($i = 0; $i < count($articulos); $i++) {

                $bgnegro    = "black";
                $bgblanco   = "white";
                $bgplomo    = "gray";
                $bglanvanda = "lavender";
                $bgrosado   = "pink";
                $gbverde    = "lightgreen";

                $blanco         = "#FFFFFF";
                $negro          = "#000000";
                $beige          = "#F5F5DC";
                $plomo          = "#808080";
                $turquesa       = "#40E0D0";
                $chicle         = "#FF69B4";
                $coral          = "#FF7F50";
                $celeste        = "#ADD8E6";
                $rosado         = "#FFC0CB";
                $rojo           = "#FF0000";
                $azalea         = "#FF00FF"; //fucsia
                $perla          = "#FAFAD2"; //crema
                $verdeLima      = "#32CD32";
                $vaqua          = "#66CDAA";
                $lila           = "#9370DB";
                $marron         = "#A52A2A";
                $vino           = "#8B0000";
                $uva            = "#800080";
                $azulino        = "#0000FF";
                $amarillo       = "#FFFF00";
                $melon          = "#FA8072";
                $cobalto        = "#008080";
                $verde          = "#008000";
                $chileBajo      = "#FF69B4";
                $verdeEsmeralda = "#009D71";
                $azulMarino     = "#252440";
                $azulItalia     = "#0000FF";
                $acero          = "#4682B4";
                $plomoOscuro    = "#808080";
                $paloRosa       = "#F7BFBE";
                $beigeJasped    = "#F5F5DC";
                $pasionCoral    = "#d96d62";
                $arena          = "#EDC9AF";
                $vaquaJasped    = "#66CDAA";
                $celesteJasped  = "#ADD8E6";
                $paloRosaJasped = "#F7BFBE";
                $verdeMilitar   = "#4E7228";
                $charcoal       = "#808080";
                $verdePera      = "#008000";
                $turquesaBebe   = "#40E0D0";
                $vinoOscuro     = "#56070C";
                $verdePino      = "#2C5545";


                $neutro = "#000000";


                /* 
            todo> COLORES
            */

                if ($articulos[$i]["cod_color"] == "01") {

                    $colores = "<b><span style='color:" . $blanco . "; background-color:" . $bgplomo . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "02") {

                    $colores = "<b><span style='color:" . $beige . "; background-color:" . $bgnegro . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "03") {

                    $colores = "<b><span style='color:" . $negro . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "04") {

                    $colores = "<b><span style='color:" . $plomo . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "05") {

                    $colores = "<b><span style='color:" . $turquesa . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "06") {

                    $colores = "<b><span style='color:" . $chicle . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "07") {

                    $colores = "<b><span style='color:" . $coral . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "08") {

                    $colores = "<b><span style='color:" . $celeste . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "09") {

                    $colores = "<b><span style='color:" . $rosado . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "10") {

                    $colores = "<b><span style='color:" . $rojo . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "11" || $articulos[$i]["cod_color"] == "26") {

                    $colores = "<b><span style='color:" . $azalea . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "12" || $articulos[$i]["cod_color"] == "14") {

                    $colores = "<b><span style='color:" . $perla . "; background-color:" . $bgnegro . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "13") {

                    $colores = "<b><span style='color:" . $verdeLima . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "18") {

                    $colores = "<b><span style='color:" . $vaqua . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "19") {

                    $colores = "<b><span style='color:" . $lila . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "20") {

                    $colores = "<b><span style='color:" . $marron . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "21") {

                    $colores = "<b><span style='color:" . $vino . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "22") {

                    $colores = "<b><span style='color:" . $uva . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "23") {

                    $colores = "<b><span style='color:" . $azulino . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "28") {

                    $colores = "<b><span style='color:" . $amarillo . "; background-color:" . $bgnegro . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "29") {

                    $colores = "<b><span style='color:" . $melon . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "30") {

                    $colores = "<b><span style='color:" . $cobalto . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "32") {

                    $colores = "<b><span style='color:" . $chileBajo . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "33") {

                    $colores = "<b><span style='color:" . $verdeEsmeralda . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "43") {

                    $colores = "<b><span style='color:" . $azulMarino . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "44") {

                    $colores = "<b><span style='color:" . $azulItalia . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "46") {

                    $colores = "<b><span style='color:" . $acero . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "53") {

                    $colores = "<b><span style='color:" . $plomoOscuro . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "55") {

                    $colores = "<b><span style='color:" . $paloRosa . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "56") {

                    $colores = "<b><span style='color:" . $beigeJasped . "; background-color:" . $bgnegro . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "60") {

                    $colores = "<b><span style='color:" . $pasionCoral . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "62") {

                    $colores = "<b><span style='color:" . $arena . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "64") {

                    $colores = "<b><span style='color:" . $vaquaJasped . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "65") {

                    $colores = "<b><span style='color:" . $celesteJasped . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "66") {

                    $colores = "<b><span style='color:" . $paloRosaJasped . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "67") {

                    $colores = "<b><span style='color:" . $verdeMilitar . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "72") {

                    $colores = "<b><span style='color:" . $charcoal . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "76") {

                    $colores = "<b><span style='color:" . $verdePera . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "77") {

                    $colores = "<b><span style='color:" . $turquesaBebe . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "80") {

                    $colores = "<b><span style='color:" . $vinoOscuro . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else if ($articulos[$i]["cod_color"] == "81") {

                    $colores = "<b><span style='color:" . $verdePino . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                } else {
                    //blanco 2 - naranja - platano - surtido - perico 
                    $colores = "<b><span style='color:" . $negro . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["color"] . "</span></b>";
                }

                /* 
            todo: Modelo
            */
                $modelo = "<b><span style='font-size:100%' class='text-primary'>" . $articulos[$i]["modelo"] . "</span></b>";

                /* 
            todo: Proyección
            */
                $proyeccionI = number_format($articulos[$i]["proyeccion"], 0);
                $proyeccion = "<center><span style='font-size:100%' class='text-success'>" . $proyeccionI . "</span></center>";

                /* 
            todo: Avance %
            */
                if ($articulos[$i]["avance"] <= 90) {

                    $avanceI = number_format($articulos[$i]["avance"], 2);
                    $avance = "<center><b><span style='font-size:100%' class='text-success'>" . $avanceI . " %</span></b></center>";
                } else {

                    $avanceI = number_format($articulos[$i]["avance"], 2);
                    $avance = "<center><b><span style='font-size:100%' class='text-danger'>" . $avanceI . " %</span></b></center>";
                }

                /* 
            todo: Estado
            */
                if ($articulos[$i]["estado"] == "Descontinuado") {

                    $estado = "<span style='font-size:80%' class='label label-danger'>Inactivo</span>";
                } else if ($articulos[$i]["estado"] == "CampañaD") {

                    $estado = "<span style='font-size:80%' class='label label-warning'>CampañaD</span>";
                } else {

                    $estado = "<span style='font-size:80%' class='label label-success'>Activo</span>";
                }

                /* 
            todo: Stock
            */
                if ($articulos[$i]["stockB"] < 0) {

                    $stockI = number_format($articulos[$i]["stockB"], 0);
                    $stock = "<center><span style='font-size:85%' class='label label-danger'>" . $stockI . "</span></center>";
                } else {

                    $stockI = number_format($articulos[$i]["stockB"], 0);
                    $stock = "<center><b>" . $stockI . "</b></center>";
                }

                /* 
            todo: Pedidos
            */
                $pedidosI = number_format($articulos[$i]["pedidos"], 0);
                $pedidos = "<center><b><span style='font-size:100%' class='text-default'>" . $pedidosI . "</span></b></center>";

                /* 
            todo: Taller
            */
                if ($articulos[$i]["taller"] <= 0) {

                    $tallerI = number_format($articulos[$i]["taller"], 0);
                    $taller = "<center><b><span style='font-size:100%' class='text-danger'>" . $tallerI . "</span></b></center>";
                } else {

                    $tallerI = number_format($articulos[$i]["taller"], 0);
                    $taller = "<center><b><span style='font-size:100%' class='text-primary'>" . $tallerI . "</span></b></center>";
                }

                /* 
            todo: Servicio
            */
                if ($articulos[$i]["servicio"] <= 0) {

                    $servicioI = number_format($articulos[$i]["servicio"], 0);
                    $servicio = "<center><b><span style='font-size:100%' class='text-danger'>" . $servicioI . "</span></b></center>";
                } else {

                    $servicioI = number_format($articulos[$i]["servicio"], 0);
                    $servicio = "<center><b><span style='font-size:100%' class='text-primary'>" . $servicioI . "</span></b></center>";
                }


                // Arreglos
                if ($articulos[$i]["arreglos"] <= 0) {
                    $arreglosI = number_format($articulos[$i]["arreglos"], 0);
                    $arreglos = "<center><b><span style='font-size:100%' class='text-danger'>" . $arreglosI . "</span></b></center>";
                } else {
                    $arreglosI = number_format($articulos[$i]["arreglos"], 0);
                    $arreglos = "<center><b><span style='font-size:100%' class='text-primary'>" . $arreglosI . "</span></b></center>";
                }


                /* 
            todo: Almacen de corte
            */
                if ($articulos[$i]["alm_corte"] <= 0) {

                    $alm_corteI = number_format($articulos[$i]["alm_corte"], 0);
                    $alm_corte = "<center><b><span style='font-size:100%' class='text-danger'>" . $alm_corteI . "</span></b></center>";
                } else {

                    $alm_corteI = number_format($articulos[$i]["alm_corte"], 0);
                    $alm_corte = "<center><b><span style='font-size:100%' class='text-success'>" . $alm_corteI . "</span></b></center>";
                }

                /* 
            todo: Ord Corte
            */
                if ($articulos[$i]["ord_corte"] > 0) {

                    $ord_corteI = number_format($articulos[$i]["ord_corte"], 0);
                    $ord_corte = "<center><b><span style='font-size:100%' class='text-secondary'>" . $ord_corteI . "</span></b></center>";
                } else {

                    $ord_corteI = number_format($articulos[$i]["ord_corte"], 0);
                    $ord_corte = "<center><b><span style='font-size:100%' class='text-danger'>" . $ord_corteI . "</span></b></center>";
                }

                /* 
            todo: ventas 30 dias
            */
                if ($articulos[$i]["ult_mes"] > 0) {

                    $ult_mesI = number_format($articulos[$i]["ult_mes"], 0);
                    $ult_mes = "<center><b><span style='font-size:100%' class='text-info'>" . $ult_mesI . "</span></b></center>";
                } else {

                    $ult_mesI = number_format($articulos[$i]["ult_mes"], 0);
                    $ult_mes = "<center><b><span style='font-size:100%' class='text-danger'>" . $ult_mesI . "</span></b></center>";
                }

                /* 
            todo: DURA TC
            */
                if ($articulos[$i]["dura_tc"] < 3) {

                    $dura_tc = "<center><b><span style='color:" . $rojo . "; background-color:" . $bgrosado . " ;'>" . $articulos[$i]["dura_tc"] . "</span></b></center>";
                } else {

                    $dura_tc = "<center><b><span style='color:" . $azulino . "; background-color:" . $bgblanco . " ;'>" . $articulos[$i]["dura_tc"] . "</span></b></center>";
                }

                /* 
            todo: FALTANTE
            */
                if ($articulos[$i]["faltantes"] <= 0) {

                    $faltantes = "<center><b><span style='color:" . $rojo . "; background-color:" . $bgrosado . " ;'></span></b></center>";
                } else {

                    $faltantes = "<center><b><span style='color:" . $azulino . "; background-color:" . $gbverde . " ;'>" . $articulos[$i]["faltantes"] . "</span></b></center>";
                }

                /*
            todo: BOTONES
            */

                if ($articulos[$i]["alerta"] == "0") {

                    $botones =  "<div class='btn-group'><button class='btn btn-xs btn-primary btnCorteI' title='Corte Incompleto' codigo='" . $articulos[$i]["articulo"] . "' estado='1'>Completo</button><button class='btn btn-xs btn-info btnMpFaltante' codigo='" . $articulos[$i]["articulo"] . "' data-toggle='modal' data-target='#modalMpFaltante'><i class='fa fa-fire'></i></button></div>";
                } else {

                    $botones =  "<div class='btn-group'><button class='btn btn-xs btn-danger btnCorteI' title='Corte Incompleto' codigo='" . $articulos[$i]["articulo"] . "' estado='0'>Incompleto</button><button class='btn btn-xs btn-info btnMpFaltante' codigo='" . $articulos[$i]["articulo"] . "' data-toggle='modal' data-target='#modalMpFaltante'><i class='fa fa-fire'></i></button></div>";
                }



                $datosJson .= '[
                "' . $modelo . '",
                "' . $articulos[$i]["nombre"] . '",
                "' . $colores . '",
                "' . $articulos[$i]["talla"] . '",
                "' . $estado . '",
                "' . $proyeccion . '",
                "' . $avance . '",
                "' . $stock . '",
                "' . $pedidos . '",
                "' . $taller . '",
                "' . $servicio . '",
                "' . $arreglos . '",
                "' . $alm_corte . '",
                "' . $ord_corte . '",
                "' . $ult_mes . '",                
                "' . $dura_tc . '"';
                if ($esSeguimientoRecetas) {
                    $segRecNum = function ($html) {
                        return '<div class="text-right">' . str_replace(array("<center>", "</center>"), "", $html) . "</div>";
                    };

                    $consumoUnitario = "—";
                    if (
                        isset($articulos[$i]["consumo_unitario"])
                        && $articulos[$i]["consumo_unitario"] !== null
                        && $articulos[$i]["consumo_unitario"] !== ""
                    ) {
                        $consumoUnitario = number_format((float) $articulos[$i]["consumo_unitario"], 4, ".", "");
                        $consumoUnitario = '<div class="text-right"><b>' . $consumoUnitario . "</b></div>";
                    } else {
                        $consumoUnitario = '<div class="text-right">—</div>';
                    }

                    $consumoOrdCorte = "—";
                    if (
                        isset($articulos[$i]["consumo_ord_corte"])
                        && $articulos[$i]["consumo_ord_corte"] !== null
                        && $articulos[$i]["consumo_ord_corte"] !== ""
                    ) {
                        $consumoOrdCorte = number_format((float) $articulos[$i]["consumo_ord_corte"], 4, ".", "");
                        $consumoOrdCorte = '<div class="text-right"><b>' . $consumoOrdCorte . "</b></div>";
                    } else {
                        $consumoOrdCorte = '<div class="text-right">—</div>';
                    }

                    $filasSeguimientoRecetas[] = array(
                        $modelo,
                        $articulos[$i]["nombre"],
                        $colores,
                        $articulos[$i]["talla"],
                        $estado,
                        $segRecNum($stock),
                        $segRecNum($pedidos),
                        $segRecNum($taller),
                        $segRecNum($servicio),
                        $segRecNum($arreglos),
                        $segRecNum($alm_corte),
                        $segRecNum($ord_corte),
                        $consumoUnitario,
                        $consumoOrdCorte,
                        $segRecNum($ult_mes),
                        $segRecNum($dura_tc),
                    );

                    if (is_array($totalesSegReceta)) {
                        $totalesSegReceta["stock"] += (float) $articulos[$i]["stockB"];
                        $totalesSegReceta["pedidos"] += (float) $articulos[$i]["pedidos"];
                        $totalesSegReceta["taller"] += (float) $articulos[$i]["taller"];
                        $totalesSegReceta["servicio"] += (float) $articulos[$i]["servicio"];
                        $totalesSegReceta["arreglos"] += (float) $articulos[$i]["arreglos"];
                        $totalesSegReceta["alm_corte"] += (float) $articulos[$i]["alm_corte"];
                        $totalesSegReceta["ord_corte"] += (float) $articulos[$i]["ord_corte"];
                        if (
                            isset($articulos[$i]["consumo_ord_corte"])
                            && $articulos[$i]["consumo_ord_corte"] !== null
                            && $articulos[$i]["consumo_ord_corte"] !== ""
                        ) {
                            $totalesSegReceta["consumo_ord_corte"] += (float) $articulos[$i]["consumo_ord_corte"];
                        }
                    }
                }
                if (!$esSeguimientoRecetas) {
                    $datosJson .= ',
                "' . $faltantes . '",
                "' . $articulos[$i]["mp_faltante"] . '",
                "' . $botones . '"';
                }
                $datosJson .= '
                ],';
            }

            if ($esSeguimientoRecetas) {
                header("Content-Type: application/json; charset=utf-8");
                $respuesta = array(
                    "data" => $filasSeguimientoRecetas,
                    "totales" => $totalesSegReceta,
                );
                if ($mpFiltroReceta !== "") {
                    $respuesta["explosion"] = ModeloArticulos::mdlExplosionMpOrdCorteDesdeArticulos(
                        $articulos,
                        $mpFiltroReceta
                    );
                }
                echo json_encode($respuesta);
                return;
            }

            $datosJson = substr($datosJson, 0, -1);

            $datosJson .= ']

            }';

            echo $datosJson;
        } else {

            if ($esSeguimientoRecetas) {
                header("Content-Type: application/json; charset=utf-8");
                $respuesta = array(
                    "data" => array(),
                    "totales" => null,
                );
                if ($mpFiltroReceta !== "") {
                    $respuesta["explosion"] = ModeloArticulos::mdlExplosionMpOrdCorteDesdeArticulos(array(), $mpFiltroReceta);
                }
                echo json_encode($respuesta);
                return;
            }

            echo '{
                "data":[]
            }';
            return;
        }
    }
}

/*=============================================
ACTIVAR TABLA DE URGENCIAS  APT
=============================================*/
$activarUrgencias = new TablaUrgencias();
$activarUrgencias->mostrarUrgencias();
