<?php

/**
 * Ingreso masivo de segundas (crear-segundas-multi). Aislado del controlador clásico.
 */
class ControladorIngresosSegundaMulti
{

    /** Internos (sectorjf.tipo = 0 o VC legado) usan articulojf; externos cierres. */
    private static function tallerUsaArticulojf($tallerCod)
    {
        return ControladorSectores::ctrEsInterno($tallerCod);
    }

    private static function resolverCantidadIngreso($value)
    {
        if (isset($value["cantidad"]) && $value["cantidad"] !== "" && $value["cantidad"] !== null) {
            return (int) $value["cantidad"];
        }
        return isset($value["nuevaCant"]) ? (int) $value["nuevaCant"] : 0;
    }

    public function ctrCrearSegundaMulti()
    {
        if (!isset($_POST["segundaMulti"]) || $_POST["segundaMulti"] !== "1") {
            return;
        }
        if (
            !isset($_POST["nuevaGuiaIng"]) ||
            !isset($_POST["idUsuario"]) ||
            !isset($_POST["listaArticulosIngreso"]) ||
            !isset($_POST["nuevoTalleres"]) ||
            !isset($_POST["nuevoTrabajadores"]) ||
            !isset($_POST["tipoMovimiento"])
        ) {
            return;
        }

        if ($_POST["nuevoTrabajadores"] === "" || $_POST["tipoMovimiento"] === "") {
            $this->mostrarAlertaError("¡Seleccione trabajador y tipo de movimiento!", "crear-segundas-multi");
            return;
        }

        if ($_POST["listaArticulosIngreso"] === "" || $_POST["listaArticulosIngreso"] === "[]") {
            $this->mostrarAlertaError("¡No se seleccionó ningún artículo. Por favor, inténtelo de nuevo!", "crear-segundas-multi");
            return;
        }

        $listaArticulos = json_decode($_POST["listaArticulosIngreso"], true);
        if (!is_array($listaArticulos)) {
            $this->mostrarAlertaError("¡La lista de artículos no es válida!", "crear-segundas-multi");
            return;
        }

        $repTaller = $_POST["nuevoTalleres"];
        $lineas = array();

        foreach ($listaArticulos as $value) {
            $cant = self::resolverCantidadIngreso($value);
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
            $this->mostrarAlertaError("¡Indique cantidades mayores a cero en al menos una línea!", "crear-segundas-multi");
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

        $tipoMov = $_POST["tipoMovimiento"];
        $trabajador = $_POST["nuevoTrabajadores"];

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
                    "codSector" => isset($row["codSector"]) ? trim((string) $row["codSector"]) : "",
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
                $tallerLinea = $fila["codSector"] !== "" ? $fila["codSector"] : $tallerCod;
                if (self::tallerUsaArticulojf($tallerLinea)) {
                    $this->actualizarArticulosSegunda(array($fila));
                } else {
                    $this->actualizarCierresDetalleSegunda(array($fila));
                }
            }

            $respCab = $this->guardarSegundaCabeceraParams($_POST, $documento, $tallerCod, $totalGrupo, $tipoMov, $trabajador);
            if ($respCab !== "ok") {
                $this->mostrarAlertaError("¡No se pudo registrar la cabecera del taller " . $tallerCod . ". Por favor, inténtelo de nuevo!", "crear-segundas-multi");
                return;
            }

            $this->guardarDetallesSegundaConTallerDoc($subParaMovimiento, $_POST, $documento, $tallerCod, $tipoMov, $trabajador);
        }

        $nDocs = count($grupos);
        $this->mostrarAlertaExito(
            "¡Se registraron " . $nDocs . " documento(s) de segunda con la misma guía!",
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

    private function actualizarArticulosSegunda($listaArticulos)
    {
        foreach ($listaArticulos as $value) {
            $tabla = "articulojf";
            $valor = $value["articulo"];
            $item1 = "taller";
            $valor1 = $value["taller"];
            ModeloArticulos::mdlActualizarUnDato($tabla, $item1, $valor1, $valor);
        }
    }

    private function actualizarCierresDetalleSegunda($listaArticulos)
    {
        foreach ($listaArticulos as $value) {
            $tabla = "cierres_detallejf";
            $valor = $value["idCierre"];
            $articulo = $value["articulo"];
            $item1 = "cantidad";
            $valor1 = $value["taller"];
            ModeloArticulos::mdlActualizarUnCierre($tabla, $item1, $valor1, $valor);
            $valor2 = $value["cantidad"];
            ModeloArticulos::mdlActualizarArticuloServicio($articulo, $valor2);
            ModeloArticulos::mdlDescontarTallerArticulo($articulo, (int) $valor2);
        }
    }

    private function guardarSegundaCabeceraParams($postData, $documento, $tallerCod, $total, $tipoMov, $trabajador)
    {
        $datos = array(
            "tipo" => $tipoMov,
            "usuario" => $postData["idUsuario"],
            "guia" => $postData["nuevaGuiaIng"],
            "taller" => $tallerCod,
            "documento" => $documento,
            "total" => $total,
            "fecha" => $postData["nuevaFecha"],
            "almacen" => "02",
            "trabajador" => $trabajador
        );

        return ModeloIngresos::mdlGuardarSegunda("movimientos_cabecerajf", $datos);
    }

    private function guardarDetallesSegundaConTallerDoc($listaArticulos, $postData, $documento, $tallerCod, $tipoMov, $trabajador)
    {
        $fecha = $postData["nuevaFecha"];

        foreach ($listaArticulos as $value) {
            $datosD = array(
                "tipo" => $tipoMov,
                "documento" => $documento,
                "taller" => $tallerCod,
                "fecha" => $fecha,
                "articulo" => $value["articulo"],
                "cliente" => $trabajador,
                "cantidad" => $value["cantidad"],
                "almacen" => "02",
                "idcierre" => $value["idCierre"]
            );

            ModeloIngresos::mdlGuardarDetalleSegunda("movimientosjf_2026", $datosD);
        }
    }
}
