<?php

require_once "../../controladores/articulos.controlador.php";
require_once "../../modelos/articulos.modelo.php";
require_once "../../controladores/sectores.controlador.php";
require_once "../../modelos/sectores.modelo.php";

/**
 * Tabla unificada para crear-ingresos-multi: varios talleres en un solo JSON.
 * GET: perfil (reservado), alcance = todos | internos | externos
 */
class TablaArticulosTallerIngresosMulti
{
    private static $listaTaller = ["T0", "T1", "T2", "T3", "T4", "T5", "T6", "T8", "T9", "TA", "TB", "TC", "TD", "T11", "TE", "T12"];

    private static $talleresInternos = ["T1", "T3"];

    public function mostrar()
    {
        $alcance = isset($_GET["alcance"]) ? $_GET["alcance"] : "todos";
        if (!in_array($alcance, ["todos", "internos", "externos"], true)) {
            $alcance = "todos";
        }

        $sectores = ControladorSectores::ctrMostrarSectores(null);
        $codigosEnLista = [];

        foreach ($sectores as $row) {
            $cod = $row["cod_sector"];
            if (!in_array($cod, self::$listaTaller, true)) {
                continue;
            }
            $codigosEnLista[] = $cod;
        }

        $codigosExternos = array_values(array_diff($codigosEnLista, self::$talleresInternos));

        $lotes = [];

        if ($alcance === "internos") {
            foreach (controladorArticulos::ctrIngresosMultiArticulosInternos() as $art) {
                $lotes[] = [
                    $art,
                    "interno",
                    $art["taller_logico"],
                    $art["sector_consulta"],
                ];
            }
        } elseif ($alcance === "externos") {
            foreach (controladorArticulos::ctrIngresosMultiArticulosExternos($codigosExternos) as $art) {
                $consulta = isset($art["sector_consulta"]) ? $art["sector_consulta"] : "";
                $lotes[] = [$art, "externo", $consulta, $consulta];
            }
        } else {
            foreach (controladorArticulos::ctrIngresosMultiArticulosInternos() as $art) {
                $lotes[] = [
                    $art,
                    "interno",
                    $art["taller_logico"],
                    $art["sector_consulta"],
                ];
            }
            foreach (controladorArticulos::ctrIngresosMultiArticulosExternos($codigosExternos) as $art) {
                $consulta = isset($art["sector_consulta"]) ? $art["sector_consulta"] : "";
                $lotes[] = [$art, "externo", $consulta, $consulta];
            }
        }

        $datosJson = '{"data": [';
        $primeraFila = true;

        foreach ($lotes as $meta) {
            list($articuloRow, $procesoCod, $tallerColumna, $codSectorConsulta) = $meta;
            $procesoHtml = $procesoCod === "interno"
                ? "<center><span class='label label-success'>Interno</span></center>"
                : "<center><span class='label label-primary'>Externo</span></center>";
            $celdaTaller = "<center><strong style='white-space:nowrap'>" . $tallerColumna . "</strong></center>";

            $fila = $this->construirFila(
                $articuloRow,
                $tallerColumna,
                $codSectorConsulta,
                $procesoCod,
                $procesoHtml,
                $celdaTaller
            );
            if (!$primeraFila) {
                $datosJson .= ",";
            }
            $datosJson .= $fila;
            $primeraFila = false;
        }

        if ($primeraFila) {
            echo '{"data":[]}';
            return;
        }

        $datosJson .= "]}";
        echo $datosJson;
    }

    /**
     * @param string $tallerColumna Taller lógico (columna y data-sector-cod): internos T1/T3 por modelo; externos = sector consultado
     * @param string $codSectorConsulta Sector con el que se consultó el stock (T1, T3, T4…)
     */
    private function construirFila($art, $tallerColumna, $codSectorConsulta, $procesoCod, $procesoHtml, $celdaTaller)
    {
        $bgblanco = "white";
        $bgrosado = "pink";
        $cobalto = "#008080";
        $rojo = "#FF0000";
        $neutro = "#000000";
        $vino = "#8B0000";

        if ($art["guia"] == "") {
            $articuloIngresoKey = $art["articulo"];
        } else {
            $articuloIngresoKey = $art["id"];
        }

        $filaIngresoKey = $tallerColumna . "|" . $articuloIngresoKey . "|" . $codSectorConsulta;

        if ($art["guia"] == "") {
            $botones = "<div class='btn-group'><button class='btn btn-primary btn-xs agregarArtiTaller recuperarBoton' articuloIngreso='" . $art["articulo"] . "' taller='" . $art["taller"] . "' articulo='" . $art["articulo"] . "' idCierre='" . $art["id"] . "' data-sector-cod='" . $tallerColumna . "' data-sector-consulta='" . $codSectorConsulta . "' data-proceso='" . $procesoCod . "' data-fila-ingreso-key='" . $filaIngresoKey . "'><i class='fa fa-plus-circle'></i></button></div>";
        } else {
            $botones = "<div class='btn-group'><button class='btn btn-primary btn-xs agregarArtiTaller recuperarBoton' articuloIngreso='" . $art["id"] . "' taller='" . $art["taller"] . "' articulo='" . $art["articulo"] . "' idCierre='" . $art["id"] . "' data-sector-cod='" . $tallerColumna . "' data-sector-consulta='" . $codSectorConsulta . "' data-proceso='" . $procesoCod . "' data-fila-ingreso-key='" . $filaIngresoKey . "'><i class='fa fa-plus-circle'></i></button></div>";
        }

        if ($art["stock"] >= 0) {
            $stock = "<center><b><span style='color:" . $cobalto . "; background-color:" . $bgblanco . " ;'>" . $art["stock"] . "</span></b></center>";
        } else {
            $stock = "<center><b><span style='color:" . $rojo . "; background-color:" . $bgrosado . " ;'>" . $art["stock"] . "</span></b></center>";
        }

        if ($art["ord_corte"] > 0) {
            $ord_corte = "<center><b><span style='color:" . $neutro . "; background-color:" . $bgblanco . " ;'>" . $art["ord_corte"] . "</span></b></center>";
        } else {
            $ord_corte = "<center><b><span style='color:" . $vino . "; background-color:" . $bgrosado . " ;'>" . $art["ord_corte"] . "</span></b></center>";
        }

        if ($art["alm_corte"] > 0) {
            $alm_corte = "<center><b><span style='color:" . $neutro . "; background-color:" . $bgblanco . " ;'>" . $art["alm_corte"] . "</span></b></center>";
        } else {
            $alm_corte = "<center><b><span style='color:" . $vino . "; background-color:" . $bgrosado . " ;'>" . $art["alm_corte"] . "</span></b></center>";
        }

        if ($art["taller"] > 0) {
            $tallerQty = "<center><b><span style='color:" . $neutro . "; background-color:" . $bgblanco . " ;'>" . $art["taller"] . "</span></b></center>";
        } else {
            $tallerQty = "<center><b><span style='color:" . $vino . "; background-color:" . $bgrosado . " ;'>" . $art["taller"] . "</span></b></center>";
        }

        return '[
            "<center>' . $art["guia"] . '</center>",
            "' . $celdaTaller . '",
            "' . $procesoHtml . '",
            "<center>' . $art["modelo"] . '</center>",
            "<center>' . $art["color"] . '</center>",
            "<center>' . $art["talla"] . '</center>",
            "' . $stock . '",
            "' . $tallerQty . '",
            "' . $alm_corte . '",
            "' . $ord_corte . '",
            "<center>' . $botones . '</center>"
            ]';
    }
}

$activar = new TablaArticulosTallerIngresosMulti();
$activar->mostrar();
