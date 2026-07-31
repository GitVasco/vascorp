<?php

require_once "../../controladores/produccion.controlador.php";
require_once "../../modelos/produccion.modelo.php";


class TablaPagos
{

    /*=============================================
    MOSTRAR LA TABLA DE ARTICULOS
    =============================================*/

    public function mostrarTablaPagos()
    {

        $pagos = ControladorProduccion::ctrMostrarPagos($_GET["inicio"], $_GET["fin"], $_GET["nquincena"], $_GET["id"], $_GET["sectorTra"]);

        if (count($pagos) > 0) {

            if ($_GET["nquincena"] == "1") {

                $datosJson = '{
                    "data": [';

                for ($i = 0; $i < count($pagos); $i++) {

                    $vino = "#8B0000";
                    $azulino = "#0000FF";
                    $verde = "#008000";

                    /* 
                        *d1    
                        */
                    if ($pagos[$i]["d1"] > 0) {

                        $d1 = number_format($pagos[$i]["d1"], 2);
                    } else {

                        $d1 = '';
                    }

                    /* 
                        *d2    
                        */
                    if ($pagos[$i]["d2"] > 0) {

                        $d2 = number_format($pagos[$i]["d2"], 2);
                    } else {

                        $d2 = '';
                    }

                    /* 
                        *d3   
                        */
                    if ($pagos[$i]["d3"] > 0) {

                        $d3 = number_format($pagos[$i]["d3"], 2);
                    } else {

                        $d3 = '';
                    }

                    /* 
                        *d4    
                        */
                    if ($pagos[$i]["d4"] > 0) {

                        $d4 = number_format($pagos[$i]["d4"], 2);
                    } else {

                        $d4 = '';
                    }

                    /* 
                        *d5    
                        */
                    if ($pagos[$i]["d5"] > 0) {

                        $d5 = number_format($pagos[$i]["d5"], 2);
                    } else {

                        $d5 = '';
                    }

                    /* 
                        *d6    
                        */
                    if ($pagos[$i]["d6"] > 0) {

                        $d6 = number_format($pagos[$i]["d6"], 2);
                    } else {

                        $d6 = '';
                    }

                    /* 
                        *d7    
                        */
                    if ($pagos[$i]["d7"] > 0) {

                        $d7 = number_format($pagos[$i]["d7"], 2);
                    } else {

                        $d7 = '';
                    }

                    /* 
                        *d8    
                        */
                    if ($pagos[$i]["d8"] > 0) {

                        $d8 = number_format($pagos[$i]["d8"], 2);
                    } else {

                        $d8 = '';
                    }

                    /* 
                        *d9    
                        */
                    if ($pagos[$i]["d9"] > 0) {

                        $d9 = number_format($pagos[$i]["d9"], 2);
                    } else {

                        $d9 = '';
                    }

                    /* 
                        *d10    
                        */
                    if ($pagos[$i]["d10"] > 0) {

                        $d10 = number_format($pagos[$i]["d10"], 2);
                    } else {

                        $d10 = '';
                    }

                    /* 
                        *d11    
                        */
                    if ($pagos[$i]["d11"] > 0) {

                        $d11 = number_format($pagos[$i]["d11"], 2);
                    } else {

                        $d11 = '';
                    }

                    /* 
                        *d12    
                        */
                    if ($pagos[$i]["d12"] > 0) {

                        $d12 = number_format($pagos[$i]["d12"], 2);
                    } else {

                        $d12 = '';
                    }

                    /* 
                        *d13    
                        */
                    if ($pagos[$i]["d13"] > 0) {

                        $d13 = number_format($pagos[$i]["d13"], 2);
                    } else {

                        $d13 = '';
                    }

                    /* 
                        *d14    
                        */
                    if ($pagos[$i]["d14"] > 0) {

                        $d14 = number_format($pagos[$i]["d14"], 2);
                    } else {

                        $d14 = '';
                    }

                    /* 
                        *d15    
                        */
                    if ($pagos[$i]["d15"] > 0) {

                        $d15 = number_format($pagos[$i]["d15"], 2);
                    } else {

                        $d15 = '';
                    }

                    /* 
                        *d16    
                        */
                    if ($pagos[$i]["d16"] > 0) {

                        $d16 = number_format($pagos[$i]["d16"], 2);
                    } else {

                        $d16 = '';
                    }

                    /* 
                        *d27
                        */
                    if ($pagos[$i]["d27"] > 0) {

                        $d27 = number_format($pagos[$i]["d27"], 2);
                    } else {

                        $d27 = '';
                    }

                    /* 
                        *d28    
                        */
                    if ($pagos[$i]["d28"] > 0) {

                        $d28 = number_format($pagos[$i]["d28"], 2);
                    } else {

                        $d28 = '';
                    }

                    /* 
                        *d29    
                        */
                    if ($pagos[$i]["d29"] > 0) {

                        $d29 = number_format($pagos[$i]["d29"], 2);
                    } else {

                        $d29 = '';
                    }

                    /* 
                        *d30    
                        */
                    if ($pagos[$i]["d30"] > 0) {

                        $d30 = number_format($pagos[$i]["d30"], 2);
                    } else {

                        $d30 = '';
                    }

                    /* 
                        *d31    
                        */
                    if ($pagos[$i]["d31"] > 0) {

                        $d31 = number_format($pagos[$i]["d31"], 2);
                    } else {

                        $d31 = '';
                    }

                    /* 
                        *TOTALES
                        */
                    if ($pagos[$i]["total"] >= 500) {

                        $total = "<b class='azul'>" . number_format($pagos[$i]["total"], 2) . "</b>";
                    } else {

                        $total = "<b class='guinda'>" . number_format($pagos[$i]["total"], 2) . "</b>";
                    }

                    $bono = number_format($pagos[$i]["bono"], 2);
                    $totalPagar = "<b class='azul'>" . number_format($pagos[$i]["total_pagar"], 2) . "</b>";

                    $datosJson .= '[
                        "' . $pagos[$i]["trabajador"] . '",
                        "<b>' . $pagos[$i]["nom_tra"] . '</b>",
                        "<b>' . $d27 . '</b>",
                        "<b>' . $d28 . '</b>",
                        "<b>' . $d29 . '</b>",
                        "<b>' . $d30 . '</b>",
                        "<b>' . $d31 . '</b>",
                        "<b>' . $d1 . '</b>",
                        "<b>' . $d2 . '</b>",
                        "<b>' . $d3 . '</b>",
                        "<b>' . $d4 . '</b>",
                        "<b>' . $d5 . '</b>",
                        "<b>' . $d6 . '</b>",
                        "<b>' . $d7 . '</b>",
                        "<b>' . $d8 . '</b>",
                        "<b>' . $d9 . '</b>",
                        "<b>' . $d10 . '</b>",
                        "<b>' . $d11 . '</b>",
                        "<b>' . $d12 . '</b>",
                        "<b>' . $d13 . '</b>",
                        "<b>' . $d14 . '</b>",
                        "<b>' . $d15 . '</b>",
                        "<b>' . $d16 . '</b>",
                        "' . $total . '",
                        "<b>' . $bono . '</b>",
                        "' . $totalPagar . '"
                        ],';
                }

                $datosJson = substr($datosJson, 0, -1);

                $datosJson .= '] 
            
                        }';

                echo $datosJson;
            } else {

                $datosJson = '{
                    "data": [';

                for ($i = 0; $i < count($pagos); $i++) {

                    $vino = "#8B0000";
                    $azulino = "#0000FF";
                    $verde = "#008000";

                    /* 
                        *d1    
                        */
                    if ($pagos[$i]["d1"] > 0) {

                        $d1 = number_format($pagos[$i]["d1"], 2);
                    } else {

                        $d1 = '';
                    }

                    /* 
                        *d12    
                        */
                    if ($pagos[$i]["d12"] > 0) {

                        $d12 = number_format($pagos[$i]["d12"], 2);
                    } else {

                        $d12 = '';
                    }

                    /* 
                        *d13    
                        */
                    if ($pagos[$i]["d13"] > 0) {

                        $d13 = number_format($pagos[$i]["d13"], 2);
                    } else {

                        $d13 = '';
                    }

                    /* 
                        *d14    
                        */
                    if ($pagos[$i]["d14"] > 0) {

                        $d14 = number_format($pagos[$i]["d14"], 2);
                    } else {

                        $d14 = '';
                    }

                    /* 
                        *d15    
                        */
                    if ($pagos[$i]["d15"] > 0) {

                        $d15 = number_format($pagos[$i]["d15"], 2);
                    } else {

                        $d15 = '';
                    }

                    /* 
                        *d16    
                        */
                    if ($pagos[$i]["d16"] > 0) {

                        $d16 = number_format($pagos[$i]["d16"], 2);
                    } else {

                        $d16 = '';
                    }

                    /* 
                        *d17    
                        */
                    if ($pagos[$i]["d17"] > 0) {

                        $d17 = number_format($pagos[$i]["d17"], 2);
                    } else {

                        $d17 = '';
                    }

                    /* 
                        *d18    
                        */
                    if ($pagos[$i]["d18"] > 0) {

                        $d18 = number_format($pagos[$i]["d18"], 2);
                    } else {

                        $d18 = '';
                    }

                    /* 
                        *d19    
                        */
                    if ($pagos[$i]["d19"] > 0) {

                        $d19 = number_format($pagos[$i]["d19"], 2);
                    } else {

                        $d19 = '';
                    }

                    /* 
                        *d20    
                        */
                    if ($pagos[$i]["d20"] > 0) {

                        $d20 = number_format($pagos[$i]["d20"], 2);
                    } else {

                        $d20 = '';
                    }

                    /* 
                        *d21    
                        */
                    if ($pagos[$i]["d21"] > 0) {

                        $d21 = number_format($pagos[$i]["d21"], 2);
                    } else {

                        $d21 = '';
                    }

                    /* 
                        *d22    
                        */
                    if ($pagos[$i]["d22"] > 0) {

                        $d22 = number_format($pagos[$i]["d22"], 2);
                    } else {

                        $d22 = '';
                    }

                    /* 
                        *d23    
                        */
                    if ($pagos[$i]["d23"] > 0) {

                        $d23 = number_format($pagos[$i]["d23"], 2);
                    } else {

                        $d23 = '';
                    }

                    /* 
                        *d24    
                        */
                    if ($pagos[$i]["d24"] > 0) {

                        $d24 = number_format($pagos[$i]["d24"], 2);
                    } else {

                        $d24 = '';
                    }

                    /* 
                        *d25    
                        */
                    if ($pagos[$i]["d25"] > 0) {

                        $d25 = number_format($pagos[$i]["d25"], 2);
                    } else {

                        $d25 = '';
                    }

                    /* 
                        *d26    
                        */
                    if ($pagos[$i]["d26"] > 0) {

                        $d26 = number_format($pagos[$i]["d26"], 2);
                    } else {

                        $d26 = '';
                    }

                    /* 
                        *d27    
                        */
                    if ($pagos[$i]["d27"] > 0) {

                        $d27 = number_format($pagos[$i]["d27"], 2);
                    } else {

                        $d27 = '';
                    }

                    /* 
                        *d28    
                        */
                    if ($pagos[$i]["d28"] > 0) {

                        $d28 = number_format($pagos[$i]["d28"], 2);
                    } else {

                        $d28 = '';
                    }

                    /* 
                        *d29    
                        */
                    if ($pagos[$i]["d29"] > 0) {

                        $d29 = number_format($pagos[$i]["d29"], 2);
                    } else {

                        $d29 = '';
                    }

                    /* 
                        *d30    
                        */
                    if ($pagos[$i]["d30"] > 0) {

                        $d30 = number_format($pagos[$i]["d30"], 2);
                    } else {

                        $d30 = '';
                    }

                    /* 
                        *d31    
                        */
                    if ($pagos[$i]["d31"] > 0) {

                        $d31 = number_format($pagos[$i]["d31"], 2);
                    } else {

                        $d31 = '';
                    }

                    /*
                        * TOTALES                        
                        */
                    if ($pagos[$i]["total"] >= 500) {

                        $total = "<b class='azul'>" . number_format($pagos[$i]["total"], 2) . "</b>";
                    } else {

                        $total = "<b class='guinda'>" . number_format($pagos[$i]["total"], 2) . "</b>";
                    }

                    $bono = number_format($pagos[$i]["bono"], 2);
                    $totalPagar = "<b class='azul'>" . number_format($pagos[$i]["total_pagar"], 2) . "</b>";

                    $datosJson .= '[
                        "' . $pagos[$i]["trabajador"] . '",
                        "<b>' . $pagos[$i]["nom_tra"] . '</b>",
                        "<b>' . $d12 . '</b>",
                        "<b>' . $d13 . '</b>",
                        "<b>' . $d14 . '</b>",
                        "<b>' . $d15 . '</b>",
                        "<b>' . $d16 . '</b>",
                        "<b>' . $d17 . '</b>",
                        "<b>' . $d18 . '</b>",
                        "<b>' . $d19 . '</b>",
                        "<b>' . $d20 . '</b>",
                        "<b>' . $d21 . '</b>",
                        "<b>' . $d22 . '</b>",
                        "<b>' . $d23 . '</b>",
                        "<b>' . $d24 . '</b>",
                        "<b>' . $d25 . '</b>",
                        "<b>' . $d26 . '</b>",
                        "<b>' . $d27 . '</b>",
                        "<b>' . $d28 . '</b>",
                        "<b>' . $d29 . '</b>",
                        "<b>' . $d30 . '</b>",
                        "<b>' . $d31 . '</b>",
                        "<b>' . $d1 . '</b>",
                        "' . $total . '",
                        "<b>' . $bono . '</b>",
                        "' . $totalPagar . '"
                        ],';
                }

                $datosJson = substr($datosJson, 0, -1);

                $datosJson .= '] 
            
                        }';

                echo $datosJson;
            }
        } else {

            echo '{
                "data":[]
            }';
            return;
        }
    }
}

/*=============================================
ACTIVAR TABLA DE ARTICULOS
=============================================*/
$activarArticulos = new TablaPagos();
$activarArticulos->mostrarTablaPagos();
