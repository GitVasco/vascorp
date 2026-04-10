<?php

/**
 * Ingreso multi-taller (crear-ingresos-multi). Aislado del controlador clásico de ingresos.
 */
class ControladorIngresosMulti
{

    public function ctrCrearIngresoMulti()
    {
        if (!isset($_POST["ingresoMulti"]) || $_POST["ingresoMulti"] !== "1") {
            return;
        }
        if (
            !isset($_POST["nuevaGuiaIng"]) ||
            !isset($_POST["idUsuario"]) ||
            !isset($_POST["listaArticulosIngreso"]) ||
            !isset($_POST["nuevoTalleres"])
        ) {
            return;
        }

        if ($_POST["listaArticulosIngreso"] === "" || $_POST["listaArticulosIngreso"] === "[]") {
            $this->mostrarAlertaError("¡No se seleccionó ningún artículo. Por favor, inténtelo de nuevo!", "crear-ingresos-multi");
            return;
        }

        $listaArticulos = json_decode($_POST["listaArticulosIngreso"], true);
        if (!is_array($listaArticulos)) {
            $this->mostrarAlertaError("¡La lista de artículos no es válida!", "crear-ingresos-multi");
            return;
        }

        $repTaller = $_POST["nuevoTalleres"];
        $lineas = array();

        foreach ($listaArticulos as $value) {
            if (isset($value["cantidad"]) && $value["cantidad"] !== "") {
                $cant = (int) $value["cantidad"];
            } else {
                $cant = isset($value["nuevaCant"]) ? (int) $value["nuevaCant"] : 0;
            }
            if ($cant <= 0) {
                continue;
            }
            $value["_cantidadIngreso"] = $cant;
            $cod = isset($value["codSector"]) ? trim((string) $value["codSector"]) : "";
            if ($cod === "") {
                $cod = $repTaller;
            }
            $value["_tallerDoc"] = $cod;
            $lineas[] = $value;
        }

        if (count($lineas) === 0) {
            $this->mostrarAlertaError("¡Indique cantidades mayores a cero en al menos una línea!", "crear-ingresos-multi");
            return;
        }

        $grupos = array();
        foreach ($lineas as $row) {
            $k = $row["_tallerDoc"];
            if (!isset($grupos[$k])) {
                $grupos[$k] = array();
            }
            $grupos[$k][] = $row;
        }
        ksort($grupos, SORT_STRING);

        $ultimo = ModeloIngresos::mdlUltimoIngreso("movimientos_cabecerajf");
        $numBase = isset($ultimo["ultimo_codigo"]) ? (int) $ultimo["ultimo_codigo"] : 1;

        $idx = 0;
        foreach ($grupos as $tallerCod => $subLista) {
            $totalGrupo = 0;
            $subParaMovimiento = array();

            foreach ($subLista as $row) {
                $totalGrupo += $row["_cantidadIngreso"];
                $subParaMovimiento[] = array(
                    "id" => isset($row["id"]) ? $row["id"] : "",
                    "articulo" => $row["articulo"],
                    "cantidad" => $row["_cantidadIngreso"],
                    "nuevaCant" => (string) $row["_cantidadIngreso"],
                    "taller" => $row["taller"],
                    "idCierre" => isset($row["idCierre"]) ? $row["idCierre"] : "",
                    "corte" => isset($row["corte"]) ? $row["corte"] : "",
                );
            }

            $n = $numBase + $idx;
            $suf = substr(str_pad((string) $n, 4, "0", STR_PAD_LEFT), -4);
            $documento = $tallerCod . $suf;
            $idx++;

            $entaller = ControladorIngresos::ActualizarEnTaller($subParaMovimiento);
            foreach ($entaller as $idEntrada => $cantidadRestante) {
                ModeloIngresos::mdlActualizarSaldoEnTaller($idEntrada, $cantidadRestante);
            }

            foreach ($subParaMovimiento as $fila) {
                if (!isset($fila["idCierre"]) || $fila["idCierre"] === "" || $fila["idCierre"] === null) {
                    $this->actualizarArticulos(array($fila));
                } else {
                    $this->actualizarCierresDetalle(array($fila));
                }
            }

            $respCab = $this->guardarIngresoCabeceraParams($_POST, $documento, $tallerCod, $totalGrupo);
            if ($respCab !== "ok") {
                $this->mostrarAlertaError("¡No se pudo registrar la cabecera del taller " . $tallerCod . ". Por favor, inténtelo de nuevo!", "crear-ingresos-multi");
                return;
            }

            $this->guardarDetallesIngresoConTallerDoc($subParaMovimiento, $_POST, $documento, $tallerCod);
        }

        ModeloCierres::mdlActualizarServicioTotal();

        $nDocs = count($grupos);
        $this->mostrarAlertaExito(
            "¡Se registraron " . $nDocs . " documento(s) de ingreso con la misma guía!",
            "ingresos"
        );
    }

    private function mostrarAlertaError($texto, $redireccion)
    {
        echo '<script>
                swal({
                    type: "error",
                    title: "Error",
                    text: "' . $texto . '",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                }).then((result)=>{
                    if(result.value){
                        window.location="' . $redireccion . '";}
                });
            </script>';
    }

    private function mostrarAlertaExito($texto, $redireccion)
    {
        echo '<script>
                swal({
                    type: "success",
                    title: "Felicitaciones",
                    text: "' . $texto . '",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                }).then((result)=>{
                    if(result.value){
                        window.location="' . $redireccion . '";}
                });
            </script>';
    }

    private function actualizarArticulos($listaArticulos)
    {
        foreach ($listaArticulos as $value) {
            $tabla = "articulojf";
            $valor = $value["articulo"];

            $item1 = "taller";
            $valor1 = $value["taller"] < 0 ? 0 : $value["taller"];

            ModeloArticulos::mdlActualizarUnDato($tabla, $item1, $valor1, $valor);

            $item2 = "stock";
            $valor2 = $value["cantidad"];

            ModeloArticulos::mdlActualizarStockIngreso($valor, $valor2);
            ModeloArticulos::mdlActualizarStockIngreso01($valor, $valor2);
        }
    }

    private function actualizarCierresDetalle($listaArticulos)
    {
        foreach ($listaArticulos as $value) {

            $tabla = "cierres_detallejf";

            $valor = $value["idCierre"];
            $articulo = $value["articulo"];

            $item1 = "cantidad";
            $valor1 = $value["taller"] < 0 ? 0 : $value["taller"];

            ModeloArticulos::mdlActualizarUnCierre($tabla, $item1, $valor1, $valor);

            $item2 = "stock";
            $valor2 = $value["cantidad"];

            ModeloArticulos::mdlActualizarStockIngreso($articulo, $valor2);
            ModeloArticulos::mdlActualizarStockIngreso01($articulo, $valor2);

            ModeloArticulos::mdlActualizarArticuloServicio($articulo, $valor2);
        }
    }

    private function guardarIngresoCabeceraParams($postData, $documento, $tallerCod, $total)
    {
        $fecha = $postData["nuevaFecha"];
        $datos = array(
            "tipo" => "E20",
            "usuario" => $postData["idUsuario"],
            "guia" => $postData["nuevaGuiaIng"],
            "taller" => $tallerCod,
            "documento" => $documento,
            "total" => $total,
            "fecha" => $fecha,
            "almacen" => "01"
        );

        return ModeloIngresos::mdlGuardarIngreso("movimientos_cabecerajf", $datos);
    }

    private function guardarDetallesIngresoConTallerDoc($listaArticulos, $postData, $documento, $tallerCod)
    {
        $fecha = $postData["nuevaFecha"];
        $tallerSinT = substr($tallerCod, 1);
        $fechaEncode = ControladorIngresos::convertirFechaExcel($fecha);

        foreach ($listaArticulos as $value) {
            $datosD = array(
                "tipo" => "E20",
                "documento" => $documento,
                "taller" => $tallerCod,
                "fecha" => $fecha,
                "articulo" => $value["articulo"],
                "cantidad" => $value["cantidad"],
                "almacen" => "01",
                "idcierre" => $value["idCierre"],
                "corte" => strtoupper($tallerSinT . $value["corte"]) . $fechaEncode
            );

            ModeloIngresos::mdlGuardarDetalleIngreso("movimientosjf_2026", $datosD);
        }
    }
}
