<?php

use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

class ControladorCortes
{

    /*
    * MOSTRAR DATOS DE ALMACEN DE CORTE
    */
    static public function ctrMostrarCortes($valor1)
    {

        $respuesta = ModeloCortes::mdlMostrarCortes($valor1);

        return $respuesta;
    }

    /*
    * MOSTRAR DATOS DE ALMACEN DE CORTE -VERSION 2
    */
    static public function ctrMostrarCortesV($modeloCorte)
    {

        $respuesta = ModeloCortes::mdlMostrarCortesV($modeloCorte);

        return $respuesta;
    }

    /*
    * MOSTRAR DATOS DE ALMACEN DE CORTE -VERSION 2
    */
    static public function ctrMostrarEnviadosTaller($modeloTaller)
    {

        $respuesta = ModeloCortes::mdlMostrarEnviadosTaller($modeloTaller);

        return $respuesta;
    }

    /*
    * MOSTRAR TALLERES DISPONIBLES
    */
    static public function ctrMostrarTaller()
    {

        $respuesta = ModeloCortes::mdlMostrarTallerA();

        return $respuesta;
    }

    /*
    * MOSTRAR DATOS DE CORTES SUBLIMADO
    */
    static public function ctrMostrarCorteSublimado($valor1, $valor2)
    {

        $respuesta = ModeloCortes::mdlMostrarCorteSublimado($valor1, $valor2);

        return $respuesta;
    }

    /*
    *MANDAR A CORTE A TALLER
    */
    static public function ctrMandarTaller()
    {

        if (isset($_POST["nuevoArticulo"])) {

            /* 
            * registramos en la tabla taller cabecera para el código
            */

            if ($_POST["seleccionarSectorServicio"] != "") {

                $tallerCab = $_POST["seleccionarSectorServicio"];
            } else {

                $tallerCab = "VC";
            }

            // Determinar si es servicio externo: 
            // ticket == "1" (checkbox marcado) = taller interno → descontar alm_corte y pasar a taller
            // ticket != "1" (checkbox NO marcado) = taller externo → descontar alm_corte y pasar a servicio
            $ticket = isset($_POST["ticket"]) ? $_POST["ticket"] : "0";
            $es_servicio_externo = ($ticket != "1");

            $datosCab = array(
                "usuario"   => $_POST["usuario"],
                "articulo"  => $_POST["nuevoArticulo"],
                "cantidad"  => $_POST["nuevoAlmCorte"],
                "saldo"     => $_POST["nuevoAlmCorte"],
                "estado"    => "0",
                "guia"      => $_POST["nuevaGuia"],
                "taller"    => $tallerCab,
                "es_servicio_externo" => $es_servicio_externo
            );

            $respuestaCab = ModeloCortes::mdlMandarTallerCabV2($datosCab);
            $ok = (is_array($respuestaCab) && isset($respuestaCab["status"]) && $respuestaCab["status"] === "ok");
            $cantidadUsada = $ok ? (int) $respuestaCab["cantidad_usada"] : 0;

            if ($ok && $cantidadUsada > 0) {

                $ult_codigo = ModeloCortes::mdlUltCodigo();

                $datos = array(
                    "usuario" => $_POST["usuario"],
                    "articulo" => $_POST["nuevoArticulo"],
                    "cantidad" => $cantidadUsada,
                    "codigo" => $ult_codigo["ult_codigo"]
                );

                $respuesta = ModeloCortes::mdlMandarTaller($datos);

                if ($respuesta == "ok") {

                    $cod = $ult_codigo["ult_codigo"];

                    $ticket = $_POST["ticket"];

                    if ($ticket == "1" || $_POST["seleccionarSectorServicio"] == 'T1') {

                        /* 
                        * NOTA: La actualización de articulojf (alm_corte y taller) ahora se hace dentro de la transacción
                        * en mdlMandarTallerCabV2 para mantener consistencia. Ya no es necesario actualizarlo aquí.
                        */

                        /* 
                        * Mandamos a imprimir con la orden de cut para cortar cada ticket 
                        */

                        $nombre_impresora = "Star BSC10";

                        $connector = new WindowsPrintConnector($nombre_impresora);
                        $printer = new Printer($connector);

                        $fecha = date("d-m-Y");

                        if ($_POST["seleccionarSectorServicio"] != 'T1') {

                            $respuesta = ControladorCortes::ctrMostrarEnTalleres($cod);
                            //Establecemos los datos de la empresa
                            $empresa = "Corporacion Vasco S.A.C.";
                            $documento = "20513613939";

                            foreach ($respuesta as $key => $value) {

                                $printer->setFont(Printer::FONT_B);
                                $printer->setJustification(Printer::JUSTIFY_CENTER);
                                $printer->setTextSize(1, 1);
                                //Activamos negrita

                                $printer->setPrintLeftMargin(0); // margen 0
                                $printer->setEmphasis(true);
                                $printer->text(".::Corporación Vasco S.A.C::." . "\n"); //Nombre de la empresa

                                $printer->text("==================================" . "\n"); //Dirección de la empresa
                                //Quitamos negrita


                                $printer->setJustification(Printer::JUSTIFY_LEFT);

                                $printer->text("Modelo:" . $value["modelo"] . " - " . $value["nombre"] . "\n"); //Modelo

                                $printer->setEmphasis(false);

                                $printer->text("Color y Talla:  " . $value["color"] . " - T" . $value["talla"] . "\n"); //Color Y tALLA

                                $printer->text("Cantidad:  " . $value["cantidad"] . "\n"); //Cantidad
                                //Activamos negrita
                                $printer->setEmphasis(true);

                                $printer->text("Operación:" . $value["cod_operacion"] . " - " . $value["operacion"] . "\n"); //Modelo

                                $cantidad = strlen($value["codigo"]);
                                $a = substr($value["codigo"], 0, 2);
                                $b = substr($value["codigo"], 2, 2);
                                $c = substr($value["codigo"], 4, 2);
                                $d = substr($value["codigo"], 6, 2);
                                $e = substr($value["codigo"], 8, 2);
                                $item = "{C" . chr($a) . chr($b) . chr($c) . chr($d) . chr($e);
                                //BARCODE
                                $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
                                $printer->setJustification(Printer::JUSTIFY_CENTER);
                                $printer->setBarcodeWidth(8);
                                $printer->setBarcodeTextPosition(Printer::BARCODE_TEXT_BELOW);
                                $printer->barcode($item, Printer::BARCODE_CODE128);
                                $printer->feed(1);

                                $printer->cut();
                            }

                            $printer->close();
                        }
                    } else {
                        $articulo  = $_POST["nuevoArticulo"];
                        $actualizaArticuloServicio = ModeloArticulos::mdlActualizarServicioCorte($articulo, $cantidadUsada, false);

                        $sector = $_POST["seleccionarSectorServicio"];
                        $codigoServicio = ModeloServicios::mdlObtenerOCrearServicioDelDia($sector, $_POST["usuario"]);

                        if ($codigoServicio) {
                            $datosDetalle = array(
                                "articulo" => $articulo,
                                "cantidad" => $cantidadUsada,
                                "codigo" => $codigoServicio,
                                "saldo" => $cantidadUsada,
                                "cabecera_taller" => $ult_codigo["ult_codigo"]
                            );

                            $respuestaDetalle = ModeloServicios::mdlGuardarDetallesServicios("servicios_detallejf", $datosDetalle);
                        }
                    }



                    echo '<script>

                    swal({
                          type: "success",
                          title: "Se mando a taller correctamente",
                          showConfirmButton: true,
                          confirmButtonText: "Cerrar"
                          }).then(function(result){
                                    if (result.value) {

                                    window.location = "en-cortes";

                                    }
                                })

                    </script>';
                }
            }
        }
    }

    /*
    * MOSTRAR TALLERES DISPONIBLES
    */
    static public function ctrMostrarEnTalleres($articulo)
    {

        $respuesta = ModeloCortes::mdlMostrarEnTalleres($articulo);

        return $respuesta;
    }

    /*
    *MANDAR A CORTE A TALLER
    */
    static public function ctrMandarTallerTotal()
    {
        if (!isset($_POST["listaTallas"])) {
            return;
        }

        $listaTallas = json_decode($_POST["listaTallas"], true);
        if (!is_array($listaTallas) || count($listaTallas) === 0) {
            echo '<script>
                swal({
                    type: "error",
                    title: "No hay tallas para enviar",
                    text: "Debe indicar al menos una cantidad válida.",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                });
            </script>';
            return;
        }

        if ($_POST["seleccionarSectorServicioTotal"] != "") {
            $tallerCab = $_POST["seleccionarSectorServicioTotal"];
        } else {
            $tallerCab = "VC";
        }

        $ticket = isset($_POST["ticketTotal"]) ? $_POST["ticketTotal"] : "0";
        $es_servicio_externo = ($ticket != "1");

        if ($es_servicio_externo && $tallerCab === "VC") {
            echo '<script>
                swal({
                    type: "error",
                    title: "Taller requerido",
                    text: "Debe seleccionar el taller de destino.",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                });
            </script>';
            return;
        }

        $procesados = 0;
        $errores = [];

        foreach ($listaTallas as $key => $value) {
            $cantidadPedida = (int) $value["nuevaCantidad"];
            if ($cantidadPedida <= 0) {
                continue;
            }

            $datosCab = array(
                "articulo"  => $value["articulo"],
                "usuario"   => $_POST["usuario"],
                "cantidad"  => $cantidadPedida,
                "saldo"     => $cantidadPedida,
                "estado"    => "0",
                "guia"      => $_POST["nuevaGuiaT"],
                "taller"    => $tallerCab,
                "es_servicio_externo" => $es_servicio_externo
            );

            $respuestaCab = ModeloCortes::mdlMandarTallerCabV2($datosCab);
            $okTotal = (is_array($respuestaCab) && isset($respuestaCab["status"]) && $respuestaCab["status"] === "ok");
            $cantidadUsadaT = $okTotal ? (int) $respuestaCab["cantidad_usada"] : 0;

            if (!$okTotal || $cantidadUsadaT <= 0) {
                $errores[] = $value["articulo"] . ": " . (is_string($respuestaCab) ? $respuestaCab : "sin saldo disponible");
                continue;
            }

            $ult_codigo = ModeloCortes::mdlUltCodigo();

            $datos = array(
                "usuario" => $_POST["usuario"],
                "articulo" => $value["articulo"],
                "cantidad" => $cantidadUsadaT,
                "codigo" => $ult_codigo["ult_codigo"]
            );

            $respuesta = ModeloCortes::mdlMandarTaller($datos);
            if ($respuesta != "ok") {
                $errores[] = $value["articulo"] . ": error al registrar en taller";
                continue;
            }

            $cod = $ult_codigo["ult_codigo"];

            if ($ticket == "1" || $_POST["seleccionarSectorServicioTotal"] == 'T1') {
                $nombre_impresora = "Star BSC10";

                $connector = new WindowsPrintConnector($nombre_impresora);
                $printer = new Printer($connector);

                if ($_POST["seleccionarSectorServicioTotal"] != 'T1') {
                    $respuesta = ControladorCortes::ctrMostrarEnTalleres($cod);
                }
            } else {
                $articulo  = $value["articulo"];
                ModeloArticulos::mdlActualizarServicioCorte($articulo, $cantidadUsadaT, false);

                $sector = $_POST["seleccionarSectorServicioTotal"];
                $codigoServicio = ModeloServicios::mdlObtenerOCrearServicioDelDia($sector, $_POST["usuario"]);

                if ($codigoServicio) {
                    $datosDetalle = array(
                        "articulo" => $articulo,
                        "cantidad" => $cantidadUsadaT,
                        "codigo" => $codigoServicio,
                        "saldo" => $cantidadUsadaT,
                        "cabecera_taller" => $ult_codigo["ult_codigo"]
                    );

                    ModeloServicios::mdlGuardarDetallesServicios("servicios_detallejf", $datosDetalle);
                } else {
                    $errores[] = $articulo . ": no se pudo crear el servicio del día";
                }
            }

            $procesados++;
        }

        if ($procesados > 0) {
            $mensajeError = count($errores) > 0 ? implode("\\n", $errores) : "";
            $titulo = count($errores) > 0
                ? "Se enviaron $procesados talla(s), con advertencias"
                : "Se mando a taller correctamente";

            echo '<script>
                swal({
                    type: "' . (count($errores) > 0 ? "warning" : "success") . '",
                    title: "' . $titulo . '",
                    text: "' . $mensajeError . '",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                }).then(function(result){
                    if (result.value) {
                        window.location = "en-cortes";
                    }
                });
            </script>';
        } else {
            $mensajeError = count($errores) > 0 ? implode("\\n", $errores) : "No se pudo procesar ninguna talla.";
            echo '<script>
                swal({
                    type: "error",
                    title: "No se pudo enviar a taller",
                    text: "' . $mensajeError . '",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                });
            </script>';
        }
    }

    //* REGISTRAR ESTAMPADO DE LA VISTA estampado.php
    static public function ctrRegistrarEstampado()
    {

        if (isset($_POST["id_articulo"])) {

            date_default_timezone_set('America/Lima');
            $fecreg                 = new DateTime();
            $id_articulo            = $_POST["id_articulo"];
            $cortesEstampado        = isset($_POST["cortesEstampado"]) ? $_POST["cortesEstampado"] : null;
            $articulosCorte         = $_POST["articulosCorte"];
            $cantidadOrigen         = $_POST["cantidadOrigen"];
            $cantidadEstampado      = $_POST["cantidadEstampado"];
            $cantidadMerma          = $_POST["cantidadMerma"];
            $cantidadSaldo          = $_POST["cantidadSaldo"];
            $fechaEstampado         = $_POST["fechaEstampado"];
            $operarioEstampado      = $_POST["operarioEstampado"];
            $cerrarCorte            = $_POST["cerrarCorte"];
            $inicioPreparacion      = $_POST["inicioPreparacion"];
            $finPreparacion         = $_POST["finPreparacion"];
            $inicioProduccion       = $_POST["inicioProduccion"];
            $finProduccion          = $_POST["finProduccion"];
            $usuario                = $_SESSION["nombre"];
            $pcreg                  = gethostbyaddr($_SERVER['REMOTE_ADDR']);

            $datos = array(
                "corte"             => $cortesEstampado,
                "almacencorte"      => $id_articulo,
                "articulo"          => $articulosCorte,
                "cantorigen"        => $cantidadOrigen,
                "cantestampado"     => $cantidadEstampado,
                "cantmerma"         => $cantidadMerma,
                "cantsaldo"         => $cantidadSaldo,
                "fecha"             => $fechaEstampado,
                "operario"          => $operarioEstampado,
                "cerrar"            => $cerrarCorte,
                "iniprep"           => $inicioPreparacion,
                "finprep"           => $finPreparacion,
                "iniprod"           => $inicioProduccion,
                "finprod"           => $finProduccion,
                "usuario"           => $usuario,
                "pcreg"             => $pcreg,
                "fecreg"            => $fecreg->format('Y-m-d')
            );


            $respuesta = ModeloCortes::mdlRegistrarEstampado($datos);

            if ($respuesta == "ok") {

                if ($cerrarCorte == "SI" || $cantidadSaldo == 0) {
                    $estampado = 1;
                } else {
                    $estampado = 0;
                }

                $datos = array(
                    "id"        => $id_articulo,
                    "estampado" => $estampado,
                    "saldo"     => $cantidadSaldo
                );

                $rptEstampado = ModeloCortes::mdlActualizarAlmacenCorte($datos);

                echo '<script>
				// swal({
                //     type: "success",
                //     title: "El estampado fue creado correctamente",
                //     showConfirmButton: true,
                //     confirmButtonText: "Cerrar"
                //     }).then(function(result){
                //         if (result.value) {
                //         window.location = "estampado";
                //         }
                //     })
				</script>';
            }
        }
    }
}
