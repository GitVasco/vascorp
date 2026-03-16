<?php

class ControladorFacturacion
{

    /*
    * MOSTRAR CABECERA DE TEMPORAL
    */
    static public function ctrMostrarTablas($tipo, $estado, $valor)
    {

        $respuesta = ModeloFacturacion::mdlMostrarTablas($tipo, $estado, $valor);

        return $respuesta;
    }

    /*
    * MOSTRAR CABECERA DE TEMPORAL
    */
    static public function ctrVerDocumento($tipo, $documento)
    {

        $respuesta = ModeloFacturacion::mdlVerDocumento($tipo, $documento);

        return $respuesta;
    }


    /*
    * MOSTRAR CABECERA DE TEMPORAL
    */
    static public function ctrMostrarTablasB()
    {

        $respuesta = ModeloFacturacion::mdlMostrarTablasB();

        return $respuesta;
    }

    /*
    * MOSTRAR talonarios credito
    */
    static public function ctrMostrarTalonarios($item, $valor)
    {
        $tabla = "talonariosjf";
        $respuesta = ModeloFacturacion::mdlMostrarTalonarios($tabla, $item, $valor);

        return $respuesta;
    }

    /*
    * MOSTRAR talonarios debito
    */
    static public function ctrMostrarTalonariosDebito($item, $valor)
    {
        $tabla = "talonariosjf";
        $respuesta = ModeloFacturacion::mdlMostrarTalonariosDebito($tabla, $item, $valor);

        return $respuesta;
    }


    /*
    * MOSTRAR RANGO DE FECHAS DE NOTAS DE VENTA CREDITO/DEBITO
    */
    static public function ctrRangoFechasNotasCD($fechaInicial, $fechaFinal)
    {
        $respuesta = ModeloFacturacion::mdlRangoFechasNotasCD($fechaInicial, $fechaFinal);

        return $respuesta;
    }

    /*
    * MOSTRAR RANGO DE FECHAS DE FACTURAS
    */
    static public function ctrRangoFechasFacturas($fechaInicial, $fechaFinal)
    {
        $respuesta = ModeloFacturacion::mdlRangoFechasFacturas($fechaInicial, $fechaFinal);

        return $respuesta;
    }

    /*
    * MOSTRAR RANGO DE FECHA DE BOLETAS
    */
    static public function ctrRangoFechasBoletas($fechaInicial, $fechaFinal)
    {
        $respuesta = ModeloFacturacion::mdlRangoFechasBoletas($fechaInicial, $fechaFinal);

        return $respuesta;
    }

    /*
    * MOSTRAR RANGO DE FECHA DE PROFORMAS
    */
    static public function ctrRangoFechasProformas($fechaInicial, $fechaFinal)
    {
        $respuesta = ModeloFacturacion::mdlRangoFechasProformas($fechaInicial, $fechaFinal);

        return $respuesta;
    }

    /*
    * MOSTRAR RANGO DE FECHA DE PROCESAR COMPROBANTES ELECTRONICOS
    */
    static public function ctrRangoFechasProcesarCE($fechaInicial, $fechaFinal, $tipo)
    {
        $respuesta = ModeloFacturacion::mdlRangoFechasProcesarCE($fechaInicial, $fechaFinal, $tipo);

        return $respuesta;
    }

    /*
    * MOSTRAR NOTAS DE DEBITO PARA IMPRESION
    */
    static public function ctrMostrarDebitoImpresion($documento, $tipo)
    {
        $respuesta = ModeloFacturacion::mdlMostrarDebitoImpresion($documento, $tipo);

        return $respuesta;
    }

    /*
    * MOSTRAR VENTA DE NOTAS PARA IMPRESION
    */
    static public function ctrMostrarVentaImpresion($documento, $tipo)
    {
        $respuesta = ModeloFacturacion::mdlMostrarVentaImpresion($documento, $tipo);

        return $respuesta;
    }

    /*
    * MOSTRAR VENTA DE NOTAS PARA IMPRESION
    */
    static public function ctrMostrarCreditoImpresion($documento, $tipo)
    {
        $respuesta = ModeloFacturacion::mdlMostrarCreditoImpresion($documento, $tipo);

        return $respuesta;
    }

    /*
    * MOSTRAR MODELO DE NOTAS PARA IMPRESION
    */
    static public function ctrMostrarModeloImpresion($documento, $tipo)
    {
        $respuesta = ModeloFacturacion::mdlMostrarModeloImpresion($documento, $tipo);

        return $respuesta;
    }

    /*
    * MOSTRAR MODELO DE NOTAS PARA IMPRESION
    */
    static public function ctrMostrarModeloImpresionV2($tabla, $documento, $tipo, $ini, $fin)
    {
        $respuesta = ModeloFacturacion::mdlMostrarModeloImpresionV2($tabla, $documento, $tipo, $ini, $fin);

        return $respuesta;
    }

    /*
    * MOSTRAR MODELO DE NOTAS PARA IMPRESION
    */
    static public function ctrMostrarModeloImpresionV3($tabla, $documento, $tipo, $ini, $fin)
    {
        $respuesta = ModeloFacturacion::mdlMostrarModeloImpresionV3($tabla, $documento, $tipo, $ini, $fin);

        return $respuesta;
    }

    /*
    * MOSTRAR MODELO DE PROFORMAS PARA IMPRESION
    */
    static public function ctrMostrarModeloProforma($tabla, $documento, $tipo)
    {
        $respuesta = ModeloFacturacion::mdlMostrarModeloProforma($tabla, $documento, $tipo);

        return $respuesta;
    }


    /*
    * MOSTRAR UNIDADES DE BOLETA Y FACTURA PARA IMPRESION
    */
    static public function ctrMostrarUnidadesImpresion($documento, $tipo)
    {
        $respuesta = ModeloFacturacion::mdlMostrarUnidadesImpresion($documento, $tipo);

        return $respuesta;
    }


    /*
    * MOSTRAR REPORTE DE VENTA POR RESUMEN
    */
    static public function ctrMostrarVentaResumen($optipo, $opdocumento, $impuesto, $vend, $inicio, $fin)
    {
        $respuesta = ModeloFacturacion::mdlMostrarVentaResumen($optipo, $opdocumento, $impuesto, $vend, $inicio, $fin);

        return $respuesta;
    }

    /*
    * MOSTRAR REPORTE POR TIPO DE VENTA POR RESUMEN
    */
    static public function ctrMostrarTipoVentaResumen($optipo, $opdocumento, $impuesto, $vend, $inicio, $fin)
    {
        $respuesta = ModeloFacturacion::mdlMostrarTipoVentaResumen($optipo, $opdocumento, $impuesto, $vend, $inicio, $fin);

        return $respuesta;
    }

    /*
    * MOSTRAR REPORTE DE VENTA DETALLADO
    */
    static public function ctrMostrarVentaDetalle($optipo, $opdocumento, $impuesto, $vend, $inicio, $fin)
    {
        $respuesta = ModeloFacturacion::mdlMostrarVentaDetalle($optipo, $opdocumento, $impuesto, $vend, $inicio, $fin);

        return $respuesta;
    }

    /*
    * MOSTRAR REPORTE POR TIPO DE VENTA DETALLADO
    */
    static public function ctrMostrarTipoVentaDetalle($optipo, $opdocumento, $impuesto, $vend, $inicio, $fin)
    {
        $respuesta = ModeloFacturacion::mdlMostrarTipoVentaDetalle($optipo, $opdocumento, $impuesto, $vend, $inicio, $fin);

        return $respuesta;
    }


    /*
    * MOSTRAR REPORTE DE VENTA POR POSTAL RESUMEN
    */
    static public function ctrMostrarVentaPostalRsm($optipo, $opdocumento, $impuesto, $vend, $inicio, $fin)
    {
        $respuesta = ModeloFacturacion::mdlMostrarVentaPostalRsm($optipo, $opdocumento, $impuesto, $vend, $inicio, $fin);

        return $respuesta;
    }
    /*
    * MOSTRAR REPORTE POR TIPO DE VENTA  POSTAL RESUMEN
    */
    static public function ctrMostrarTipoVentaPostalRsm($optipo, $opdocumento, $impuesto, $vend, $inicio, $fin)
    {

        $respuesta = ModeloFacturacion::mdlMostrarTipoVentaPostalRsm($optipo, $opdocumento, $impuesto, $vend, $inicio, $fin);

        return $respuesta;
    }

    /*
    * MOSTRAR REPORTE DE VENTA POR POSTAL DETALLE
    */
    static public function ctrMostrarVentaPostalDet($optipo, $opdocumento, $impuesto, $vend, $inicio, $fin)
    {

        $respuesta = ModeloFacturacion::mdlMostrarVentaPostalDet($optipo, $opdocumento, $impuesto, $vend, $inicio, $fin);

        return $respuesta;
    }

    static public function ctrMostrarTipoVentaPostalDet($optipo, $opdocumento, $impuesto, $vend, $inicio, $fin)
    {

        $respuesta = ModeloFacturacion::mdlMostrarTipoVentaPostalDet($optipo, $opdocumento, $impuesto, $vend, $inicio, $fin);

        return $respuesta;
    }

    static public function ctrFacturarGuia()
    {

        if (isset($_POST["codPedido"])) {

            $codigo = $_POST["codPedido"];
            //var_dump($codigo);
            $serie = $_POST["serieDest"];
            //var_dump($serie);
            $documento = $_POST["docDest"];
            //var_dump($serie.$documento);
            $docDestino = $serie . $documento;
            //var_dump($docDestino);

            $tip_dest = substr($serie, 0, 1);
            //var_dump($tip_dest);
            date_default_timezone_set("America/Lima");
            $fecha = date("Y-m-d");
            //var_dump($fecha);
            $tipo_origen = "S01";
            //var_dump($tipo_origen);
            $usuario = $_POST["idUsuario"];

            if ($tip_dest == "F") {

                $tipo = "S03";
                //var_dump($tipo);
                $tipoCta = '01';
                //var_dump($tipoCta);
                $nombre_tipo = "FACTURA";
                //var_dump($nombre_tipo);

                $talonario = ModeloFacturacion::mdlActualizarTalonarioFactura($serie);
                //var_dump("factura", $talonario);

            } else {

                $tipo = "S02";
                //var_dump($tipo);
                $tipoCta = '03';
                //var_dump($tipoCta);
                $nombre_tipo = "BOLETA";
                //var_dump($nombre_tipo);

                $talonario = ModeloFacturacion::mdlActualizarTalonarioBoleta($serie);
                //var_dump("boleta", $talonario);

            }

            /*
            todo GENERAMOS EN MOVIMIENTOS
            */

            $datos = array(
                "tipo" => $tipo,
                "documento" => $docDestino,
                "fecha" => $fecha,
                "nombre_tipo" => $nombre_tipo,
                "codigo" => $codigo,
                "tipo_documento" => $tipo_origen
            );
            //var_dump($datos);

            $facturar = ModeloFacturacion::mdlFacturarGuiaM($datos);
            //var_dump($facturar);

            /*
            todo REGISTRAMOS EN VENTAJF
            */
            if ($facturar == "ok") {

                $usureg = $_SESSION["nombre"];
                $pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);

                $datosV = array(
                    "tipo_ori" => "S01",
                    "tipo" => $tipo,
                    "documento" => $docDestino,
                    "tipo_documento" => $nombre_tipo,
                    "doc_origen" => $codigo,
                    "usuario" => $usuario,
                    "usureg" => $usureg,
                    "pcreg" => $pcreg
                );
                //var_dump($datosV);

                $facturarV = ModeloFacturacion::mdlFacturarGuiaV($datosV);
                //$facturarV = "ok";
                //var_dump($facturar);

                if ($facturarV == "ok") {

                    $estado = ModeloFacturacion::mdlActualizarGuiaF($codigo);
                    //var_dump($estado);

                    if ($estado == "ok") {

                        $codCta = $docDestino;
                        //var_dump($codCta);
                        $tipoDoc = $tipo;

                        $respuestaDoc = ModeloFacturacion::mdlMostraVentaDocumento($codCta, $tipoDoc);
                        //var_dump($respuestaDoc);

                        $cliente = $respuestaDoc["cliente"];
                        //var_dump($cliente);
                        $vendedor = $respuestaDoc["vendedor"];
                        //var_dump($vendedor);

                        date_default_timezone_set("America/Lima");
                        $fecha = date("Y-m-d");
                        //var_dump($fecha);
                        $dias = $respuestaDoc["dias"];
                        //var_dump($dias);
                        $fecha_ven = date("Y-m-d", strtotime($fecha . "+ " . $dias . " day"));
                        //var_dump($fecha_ven);

                        $monto = $respuestaDoc["total"];
                        //var_dump($monto);
                        $saldo = $respuestaDoc["total"];
                        //var_dump($saldo);

                        $cod_pago = $tipoCta;
                        //var_dump($cod_pago);

                        $usureg = $_SESSION["nombre"];
                        $pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);

                        $datosCta = array(
                            "tipo_doc" => $tipoCta,
                            "num_cta" => $docDestino,
                            "cliente" => $cliente,
                            "vendedor" => $vendedor,
                            "fecha_ven" => $fecha_ven,
                            "monto" => $monto,
                            "cod_pago" => $cod_pago,
                            "usuario" => $usuario,
                            "saldo" => $saldo,
                            "usureg" => $usureg,
                            "pcreg" => $pcreg
                        );
                        //var_dump($datosCta);

                        $ctacte = ModeloFacturacion::mdlGenerarCtaCte($datosCta);
                        //var_dump($ctacte);

                        if ($ctacte == "ok") {

                            echo '<script>

                            swal({
                                type: "success",
                                title: "Se Genero la ' . $nombre_tipo . ' N° ' . $docDestino . '",
                                showConfirmButton: true,
                                confirmButtonText: "Cerrar",
                            }).then(function (result) {
                                if (result.value) {
                                    window.open("vistas/reportes_ticket/impresion_bolfact.php?tipo=' . $tipo . '&documento=' . $docDestino . '","_blank"
                                    );

                                    window.location = "guias-remision";
                                }
                            });

                            </script>';
                        }
                    }
                }
            }
        }
    }

    /* 
    * FACTURAR DESDE GUIA
    */
    static public function ctrFacturarB()
    {

        if (isset($_POST["codPedidoB"])) {

            $codigo = $_POST["codPedidoB"];
            //var_dump($codigo);
            $doc = $_POST["serieSeparadoB"];
            $docDestino = str_replace('-', '', $doc);
            //var_dump($docDestino);
            $tip_dest = substr($docDestino, 0, 1);
            //var_dump($tip_dest);
            date_default_timezone_set("America/Lima");
            $fecha = date("Y-m-d");
            //var_dump($fecha);
            $tipo_origen = "S01";
            //var_dump($tipo_origen);
            $usuario = $_POST["idUsuarioB"];
            //var_dump($usuario);

            $cuenta = $_POST["formaPago"];

            $serie = substr($docDestino, 0, 4);;
            //var_dump($serie);

            if ($tip_dest == "F") {

                $tipo = "S03";
                //var_dump($tipo);
                $tipoCta = '01';
                //var_dump($tipoCta);
                $nombre_tipo = "FACTURA";
                //var_dump($nombre_tipo);

                $talonario = ModeloFacturacion::mdlActualizarTalonarioFactura($serie);
                //var_dump("factura", $talonario);

            } else {

                $tipo = "S02";
                //var_dump($tipo);
                $tipoCta = '03';
                //var_dump($tipoCta);
                $nombre_tipo = "BOLETA";
                //var_dump($nombre_tipo);

                $talonario = ModeloFacturacion::mdlActualizarTalonarioBoleta($serie);
                //var_dump("boleta", $talonario);

            }

            /*
                todo GENERAMOS EN MOVIMIENTOS
                */
            $datos = array(
                "tipo" => $tipo,
                "documento" => $docDestino,
                "fecha" => $fecha,
                "nombre_tipo" => $nombre_tipo,
                "codigo" => $codigo,
                "tipo_documento" => $tipo_origen
            );
            //var_dump($datos);

            $facturar = ModeloFacturacion::mdlFacturarGuiaM($datos);
            //var_dump($facturar);

            /*
                todo REGISTRAMOS EN VENTAJF
                */
            if ($facturar == "ok") {

                $usureg = $_SESSION["nombre"];
                $pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);

                $datosV = array(
                    "tipo_ori" => "S01",
                    "tipo" => $tipo,
                    "documento" => $docDestino,
                    "tipo_documento" => $nombre_tipo,
                    "cuenta" => $cuenta,
                    "doc_origen" => $codigo,
                    "usuario" => $usuario,
                    "usureg" => $usureg,
                    "pcreg" => $pcreg
                );
                //var_dump($datosV);

                $facturarV = ModeloFacturacion::mdlFacturarGuiaV($datosV);
                //var_dump($facturarV);

                if ($facturarV == "ok") {

                    $estado = ModeloFacturacion::mdlActualizarGuiaF($codigo);
                    //var_dump($estado);

                    if ($estado == "ok") {

                        $codCta = $docDestino;
                        //var_dump($codCta);
                        $tipoDoc = $tipo;

                        $respuestaDoc = ModeloFacturacion::mdlMostraVentaDocumento($codCta, $tipoDoc);
                        //var_dump($respuestaDoc);

                        $cliente = $respuestaDoc["cliente"];
                        //var_dump($cliente);
                        $vendedor = $respuestaDoc["vendedor"];
                        //var_dump($vendedor);

                        date_default_timezone_set("America/Lima");
                        $fecha = date("Y-m-d");
                        //var_dump($fecha);
                        $dias = $respuestaDoc["dias"];
                        //var_dump($dias);
                        $fecha_ven = date("Y-m-d", strtotime($fecha . "+ " . $dias . " day"));
                        //var_dump($fecha_ven);

                        $monto = $respuestaDoc["total"];
                        //var_dump($monto);
                        $saldo = $respuestaDoc["total"];
                        //var_dump($saldo);

                        $cod_pago = $tipoCta;
                        //var_dump($cod_pago);

                        $datosCta = array(
                            "tipo_doc" => $tipoCta,
                            "num_cta" => $docDestino,
                            "cliente" => $cliente,
                            "vendedor" => $vendedor,
                            "fecha_ven" => $fecha_ven,
                            "monto" => $monto,
                            "cod_pago" => $cod_pago,
                            "usuario" => $usuario,
                            "saldo" => $saldo,
                            "usureg" => $usureg,
                            "pcreg" => $pcreg
                        );
                        //var_dump($datosCta);

                        $ctacte = ModeloFacturacion::mdlGenerarCtaCte($datosCta);
                        //var_dump($ctacte);

                        if ($ctacte == "ok") {

                            echo '<script>

                                swal({
                                        type: "success",
                                        title: "Se Genero la ' . $nombre_tipo . ' N° ' . $docDestino . '",
                                        showConfirmButton: true,
                                        confirmButtonText: "Cerrar"
                                }).then(function(result){
                                                if (result.value) {

                                                    window.open("vistas/reportes_ticket/impresion_bolfact.php?tipo=' . $tipo . '&documento=' . $docDestino . '","_blank")
                                                    window.location = "guias-remision";
                                                }
                                            })

                                </script>';
                        }
                    }
                }
            }
        }
    }


    static public function ctrFacturarSalida()
    {

        if (isset($_POST["codSalida"])) {

            if ($_POST["tdoc"] == "E25") {
                $tabla = "detalle_ing_sal";

                $respuesta = ModeloSalidas::mdlMostraDetallesTemporal($tabla, $_POST["codSalida"]);

                foreach ($respuesta as $key => $value) {
                    $respuestaGuia = ModeloArticulos::mdlActualizarTallerIngreso($value["articulo"], $value["cantidad"]);
                }


                if ($respuestaGuia == "ok") {

                    $serie = $_POST["tdoc"];
                    ModeloSalidas::mdlActualizarArgumento($serie);

                    ModeloSalidas::mdlActualizarSalidaF($_POST["codSalida"]);

                    echo '<script>

                        swal({
                            type: "success",
                            title: "El perfil ha sido editado correctamente",
                            showConfirmButton: true,
                            confirmButtonText: "Cerrar"
                            }).then(function(result) {
                                        if (result.value) {

                                        window.location = "salidas-varios";

                                        }
                                    })

                        </script>';
                }
            } else {
                //*todo: BAJAR o subir EL STOCK
                $tabla = "detalle_ing_sal";

                $respuesta = ModeloSalidas::mdlMostraDetallesTemporal($tabla, $_POST["codSalida"]);

                $almacen = $_SESSION["almacen"] == "01" ? "stock01" : "stock05";

                foreach ($respuesta as $value) {

                    $datos = array(
                        "articulo" => $value["articulo"],
                        "cantidad" => $value["cantidad"]
                    );
                    #var_dump($datos);
                    $inicioTipo = substr($_POST["tdoc"], 0, 1);

                    if ($inicioTipo == 'E') {

                        $respuestaGuia = ModeloArticulos::mdlActualizarStockIngreso($value["articulo"], $value["cantidad"]);
                        $respuestaGuia = ModeloArticulos::mdlActualizarStockIngreso01Almacen($almacen, $value["articulo"], $value["cantidad"]);
                    } else {

                        $respuestaGuia = ModeloArticulos::mdlActualizarStock($datos);
                        $respuestaGuia = ModeloArticulos::mdlActualizarStock01($almacen, $value["articulo"], $value["cantidad"]);
                    }

                    #var_dump($respuestaGuia);

                }


                //*todo: registrar en movimientos
                if ($respuestaGuia == "ok") {

                    $intoA = "";
                    $intoB = "";
                    foreach ($respuesta as $key => $value) {

                        $tipo = $_POST["tdoc"];

                        $documento = $_POST["serieSalida"];
                        $doc = $tipo . $documento;
                        //var_dump($doc);

                        $cliente = $_POST["codCli"];
                        //var_dump($cliente);

                        $vendedor = $_POST["codVen"];
                        //var_dump($vendedor);

                        $dscto = 0;
                        //var_dump($dscto);

                        date_default_timezone_set("America/Lima");
                        $fecha = date("Y-m-d");

                        $tipoMov = ModeloMovimientos::mdlCodigoMaestra("ttop", $tipo);

                        $nombre_tipo = $tipoMov["descripcion"];

                        $almacen = $_SESSION["almacen"];

                        $total = $value["cantidad"] * $value["precio"] * ((100 - $dscto) / 100);
                        //var_dump($total);

                        if ($key < count($respuesta) - 1) {

                            $intoA .= "('" . $tipo . "','" . $doc . "','" . $fecha . "','" . $value["articulo"] . "','" . $cliente . "','" . $vendedor . "'," . $value["cantidad"] . "," . $value["precio"] . ",0," . $dscto . "," . $total . ",'" . $nombre_tipo . "','" . $almacen . "'),";
                        } else {

                            $intoB .= "('" . $tipo . "','" . $doc . "','" . $fecha . "','" . $value["articulo"] . "','" . $cliente . "','" . $vendedor . "'," . $value["cantidad"] . "," . $value["precio"] . ",0," . $dscto . "," . $total . ",'" . $nombre_tipo . "','" . $almacen . "')";
                        }
                    }

                    $detalle = $intoA . $intoB;
                    #var_dump("detalle", $detalle);

                    $respuestaMovimientos = ModeloFacturacion::mdlRegistrarMovimientos($detalle);
                    #var_dump($respuestaMovimientos);   
                    //var_dump($respuestaMovimientos);

                    /*
                        todo: registrar en ventajf
                        */
                    if ($respuestaMovimientos == "ok") {

                        $respuestaDoc = ModeloSalidas::mdlMostrarSalidasCabecera($_POST["codSalida"]);
                        //var_dump($respuestaDoc);

                        $tipo = $_POST["tdoc"];

                        $documento = $_POST["serieSalida"];
                        $doc = $tipo . $documento;
                        //var_dump($doc);

                        $usuario = $_POST["idUsuario"];
                        //var_dump($usuario);

                        $docOrigen = $_POST["codSalida"];
                        //var_dump("$docOrigen");

                        //$docDestino = $_POST["serieSeparado"];
                        //$docDest = str_replace('-', '', $docDestino);
                        //var_dump($docDest);

                        $datosD = array(
                            "tipo" => $tipo,
                            "documento" => $doc,
                            "neto" => $respuestaDoc["op_gravada"],
                            "igv" => $respuestaDoc["igv"],
                            "dscto" => $respuestaDoc["descuento_total"],
                            "total" => $respuestaDoc["total"],
                            "cliente" => $respuestaDoc["cod_cli"],
                            "vendedor" => $respuestaDoc["vendedor"],
                            "agencia" => $respuestaDoc["agencia"],
                            "lista_precios" => $respuestaDoc["lista"],
                            "condicion_venta" => $respuestaDoc["condicion_venta"],
                            "doc_destino" => '',
                            "doc_origen" => $docOrigen,
                            "usuario" => $usuario,
                            "tipo_documento" => $_POST["nomTipo"]
                        );
                        //var_dump($datosD);

                        $respuestaDocumento = ModeloSalidas::mdlRegistrarDocumentoSalida($datosD);
                    }

                    var_dump($respuestaDocumento);

                    /* 
                        todo: SUMAR 1 AL DOCUMENTO
                        */
                    if ($respuestaDocumento == "ok") {

                        $serie = $_POST["tdoc"];
                        //var_dump($serie);

                        $talonario = ModeloSalidas::mdlActualizarArgumento($serie);
                    }

                    //var_dump($talonario);

                    /*
                        todo: CAMBIAR EL ESTADO DEL PEDIDO
                        */
                    if ($talonario == "ok") {

                        $estado = ModeloSalidas::mdlActualizarSalidaF($_POST["codSalida"]);

                        //var_dump($estado);

                        if ($estado == "ok") {

                            echo '<script>
    
                                swal({
                                        type: "success",
                                        title: "Se Genero el documento ' . $documento . '",
                                        showConfirmButton: true,
                                        confirmButtonText: "Cerrar"
                                }).then(function(result){
                                                if (result.value) {
    
                                                window.location = "salidas-varios";
    
                                                }
                                            })
    
                                </script>';
                        }
                    }
                }
            }
        }
    }

    static public function ctrCrearFacturaXML()
    {

        if (isset($_GET["tipoFact"]) && isset($_GET["documentoFact"])) {

            $doc = new DOMDocument();
            $doc->formatOutput = FALSE;
            $doc->preserveWhiteSpace = TRUE;
            $doc->encoding = 'utf-8';

            require_once("/../extensiones/cantidad_en_letras.php");
            require_once("/../vistas/generar_xml/signature.php"); // permite firmar xml

            $tipo = $_GET["tipoFact"];

            $documento = $_GET["documentoFact"];
            $venta = ControladorFacturacion::ctrMostrarVentaImpresion($documento, $tipo);

            $modelos = ControladorFacturacion::ctrMostrarModeloImpresion($documento, $tipo);

            $unidad = ControladorFacturacion::ctrMostrarUnidadesImpresion($documento, $tipo);
            // var_dump($modelos);

            if ($tipo == 'S03') {
                $tipcomprobante = '01';
            } else {
                $tipcomprobante = '03';
            }

            $emisor =     array(
                'tipodoc'        => '6',
                'ruc'             => '20513613939',
                'nombre_comercial' => 'JACKY FORM',
                'razon_social'    => 'Corporacion Vasco S.A.C.',
                'referencia'    => 'URB.SANTA LUISA 1RA ETAPA',
                'direccion'        => 'CAL.SANTO TORIBIO NRO. 259',
                'pais'            => 'PE',
                'departamento'  => 'LIMA',
                'provincia'        => 'LIMA',
                'distrito'        => 'SAN MARTIN DE PORRES'
            );


            $cliente = array(
                'tipodoc'        => '6', //6->ruc, 1-> dni 
                'ruc'            => $venta["dni"],
                'razon_social'  => $venta["nombre"],
                'cliente'       => $venta["cliente"],
                'direccion'        => $venta["direccion"],
                'pais'            => 'PE'
            );

            $vendedor = array(
                "codigo"        => $venta["vendedor"],
                "nombre"        => $venta["nom_vendedor"]
            );

            $comprobante =    array(
                'tipodoc'        => $tipcomprobante, //01->FACTURA, 03->BOLETA, 07->NC, 08->ND
                'serie'            => substr($venta["documento"], 0, 4),
                'correlativo'    => substr($venta["documento"], 4, 12),
                'fecha_emision' => $venta["fecha_emision"],
                'moneda'        => 'PEN', //PEN->SOLES; USD->DOLARES
                'total_opgravadas' => 0, //OP. GRAVADAS
                'total_opexoneradas' => 0,
                'total_opinafectas' => 0,
                'igv'            => 0,
                'total'            => 0,
                'total_texto'    => ''
            );



            $op_gravadas = 0;
            $op_inafectas = 0;
            $op_exoneradas = 0;

            $comprobante['total_opgravadas'] = $venta["neto"];
            $comprobante['total_opexoneradas'] = $op_exoneradas;
            $comprobante['total_opinafectas'] = $op_inafectas;
            $comprobante['igv'] = $venta["igv"];
            $comprobante['total'] = $venta["total"];
            $comprobante['total_texto'] = CantidadEnLetra($venta["total"]);
            $totalSinIGV = $venta["total"] - $venta["igv"];

            $serieGuia = substr($venta["origen2"], 0, 4);
            $correlativoGuia = substr($venta["origen2"], 4, 12);

            //RUC DEL EMISOR - TIPO DE COMPROBANTE - SERIE DEL DOCUMENTO - CORRELATIVO
            //01-> FACTURA, 03-> BOLETA, 07-> NOTA DE CREDITO, 08-> NOTA DE DEBITO, 09->GUIA DE REMISION
            $nombrexml = $emisor['ruc'] . '-' . $comprobante['tipodoc'] . '-' . $comprobante['serie'] . '-' . $comprobante['correlativo'];

            $ruta = "vistas/generar_xml/archivos_xml/" . $nombrexml;

            $tipoCliente = $cliente["ruc"];


            if (strlen($tipoCliente) == 8) {
                $tipodoc = '1';
            } else {
                $tipodoc = '6';
            }

            $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
            xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
            xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
            xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2">
            <ext:UBLExtensions>';
            if ($venta["dscto"] > 0) {
                $xml .= '<ext:UBLExtension>
                    <ext:ExtensionContent>
                        <cbc:TotalDiscount>' . $venta["dscto"] . '</cbc:TotalDiscount>
                    </ext:ExtensionContent>
                </ext:UBLExtension>';
            }

            $xml .= '<ext:UBLExtension>
                    <ext:ExtensionContent />
                </ext:UBLExtension>
            </ext:UBLExtensions>
            <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
            <cbc:CustomizationID>2.0</cbc:CustomizationID>
            <cbc:ID>' . $comprobante["serie"] . '-' . $comprobante["correlativo"] . '</cbc:ID>
            <cbc:IssueDate>' . $comprobante["fecha_emision"] . '</cbc:IssueDate>
            <cbc:InvoiceTypeCode listID="0101" listSchemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo51"
                name="Tipo de Operacion">' . $comprobante["tipodoc"] . '</cbc:InvoiceTypeCode>
            <cbc:Note languageLocaleID="1000"> ' . $comprobante["total_texto"] . '</cbc:Note>
            <cbc:Note>Nro.unidades: ' . $unidad["cantidad"] . '</cbc:Note>
            <cbc:Note languageID="D">' . $cliente["cliente"] . '</cbc:Note>
            <cbc:Note languageID="E">CONTADO .</cbc:Note>
            <cbc:Note languageID="F">' . $totalSinIGV . '</cbc:Note>
            <cbc:Note languageID="G">' . $vendedor["codigo"] . ' ' . $vendedor["nombre"] . '</cbc:Note>
            <cbc:DocumentCurrencyCode listAgencyName="United Nations Economic Commission for Europe" listID="ISO 4217 Alpha"
                listName="Currency">PEN</cbc:DocumentCurrencyCode>
            <cbc:LineCountNumeric>' . count($modelo) . '</cbc:LineCountNumeric>
            <cac:DespatchDocumentReference>
                <cbc:ID>' . $serieGuia . '-' . $correlativoGuia . '</cbc:ID>
                <cbc:DocumentTypeCode listAgencyName="PE:SUNAT" listName="Tipo de Documento"
                    listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo01">09</cbc:DocumentTypeCode>
            </cac:DespatchDocumentReference>
            <cac:Signature>
                <cbc:ID>IDSignKG</cbc:ID>
                <cac:SignatoryParty>
                    <cac:PartyIdentification>
                        <cbc:ID>' . $emisor["ruc"] . '</cbc:ID>
                    </cac:PartyIdentification>
                    <cac:PartyName>
                        <cbc:Name>' . $emisor["razon_social"] . '</cbc:Name>
                    </cac:PartyName>
                </cac:SignatoryParty>
                <cac:DigitalSignatureAttachment>
                    <cac:ExternalReference>
                        <cbc:URI>#SignST</cbc:URI>
                    </cac:ExternalReference>
                </cac:DigitalSignatureAttachment>
            </cac:Signature>
            <cac:AccountingSupplierParty>
                <cac:Party>
                    <cac:PartyIdentification>
                        <cbc:ID schemeAgencyName="PE:SUNAT" schemeID="6" schemeName="Documento de Identidad"
                            schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">' . $emisor["ruc"] . '</cbc:ID>
                    </cac:PartyIdentification>
                    <cac:PartyName>
                        <cbc:Name>' . $emisor["nombre_comercial"] . '</cbc:Name>
                    </cac:PartyName>
                    <cac:PartyLegalEntity>
                        <cbc:RegistrationName>' . $emisor["razon_social"] . '</cbc:RegistrationName>
                        <cac:RegistrationAddress>
                            <cbc:AddressTypeCode listAgencyName="PE:SUNAT" listName="Establecimientos anexos">0002
                            </cbc:AddressTypeCode>
                            <cbc:CitySubdivisionName>' . $emisor["referencia"] . '</cbc:CitySubdivisionName>
                            <cbc:CityName>' . $emisor["provincia"] . '</cbc:CityName>
                            <cbc:CountrySubentity>' . $emisor["departamento"] . '</cbc:CountrySubentity>
                            <cbc:District>' . $emisor["distrito"] . '</cbc:District>
                            <cac:AddressLine>
                                <cbc:Line>' . $emisor["direccion"] . '</cbc:Line>
                            </cac:AddressLine>
                            <cac:Country>
                                <cbc:IdentificationCode listAgencyName="United Nations Economic Commission for Europe"
                                    listID="ISO 3166-1" listName="Country">' . $emisor["pais"] . '</cbc:IdentificationCode>
                            </cac:Country>
                        </cac:RegistrationAddress>
                    </cac:PartyLegalEntity>
                </cac:Party>
            </cac:AccountingSupplierParty>
            <cac:AccountingCustomerParty>
                <cac:Party>
                    <cac:PartyIdentification>
                        <cbc:ID schemeAgencyName="PE:SUNAT" schemeID="' . $tipodoc . '" schemeName="Documento de Identidad"
                            schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">' . $cliente["ruc"] . '</cbc:ID>
                    </cac:PartyIdentification>
                    <cac:PartyName>
                        <cbc:Name />
                    </cac:PartyName>
                    <cac:PartyLegalEntity>
                        <cbc:RegistrationName>' . $cliente["razon_social"] . '</cbc:RegistrationName>
                        <cac:RegistrationAddress>
                            <cbc:ID schemeAgencyName="PE:INEI" schemeName="Ubigeos" />
                            <cbc:CitySubdivisionName>-</cbc:CitySubdivisionName>
                            <cbc:CityName />
                            <cbc:CountrySubentity>' . $venta["departamento"] . '</cbc:CountrySubentity>
                            <cbc:District />
                            <cac:AddressLine>
                                <cbc:Line>' . $cliente["direccion"] . '</cbc:Line>
                            </cac:AddressLine>
                            <cac:Country>
                                <cbc:IdentificationCode listAgencyName="United Nations Economic Commission for Europe"
                                    listID="ISO 3166-1" listName="Country">' . $cliente["pais"] . '</cbc:IdentificationCode>
                            </cac:Country>
                        </cac:RegistrationAddress>
                    </cac:PartyLegalEntity>
                    <cac:Contact>
                        <cbc:ElectronicMail>' . $venta["email"] . '</cbc:ElectronicMail>
                    </cac:Contact>
                </cac:Party>
            </cac:AccountingCustomerParty>';
            if ($venta["dscto"] > 0) {
                $flg_firma = 1; //Posicion del XML: 0 para firma
                $valor_dscto = $comprobante["total_opgravadas"] - $venta["dscto"];
                $xml .= '<cac:AllowanceCharge>
                <cbc:ChargeIndicator>false</cbc:ChargeIndicator>
                <cbc:AllowanceChargeReasonCode listAgencyName="PE:SUNAT" listName="Cargo/descuento"
                    listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo53">02</cbc:AllowanceChargeReasonCode>
                <cbc:Amount currencyID="PEN">' . $venta["dscto"] . '</cbc:Amount>
            </cac:AllowanceCharge>';
            } else {
                $flg_firma = 0; //Posicion del XML: 0 para firma
                $valor_dscto = $comprobante["total_opgravadas"];
            }

            $xml .= '<cac:TaxTotal>
                <cbc:TaxAmount currencyID="' . $comprobante["moneda"] . '">' . $comprobante["igv"] . '</cbc:TaxAmount>
                <cac:TaxSubtotal>
                    <cbc:TaxableAmount currencyID="' . $comprobante["moneda"] . '">' . $comprobante["total_opgravadas"] . '</cbc:TaxableAmount>
                    <cbc:TaxAmount currencyID="' . $comprobante["moneda"] . '">' . $comprobante["igv"] . '</cbc:TaxAmount>
                    <cac:TaxCategory>
                        <cac:TaxScheme>
                            <cbc:ID schemeAgencyID="6" schemeID="UN/ECE 5153">1000</cbc:ID>
                            <cbc:Name>IGV</cbc:Name>
                            <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                        </cac:TaxScheme>
                    </cac:TaxCategory>
                </cac:TaxSubtotal>
            </cac:TaxTotal>
            <cac:LegalMonetaryTotal>
                <cbc:LineExtensionAmount currencyID="' . $comprobante["moneda"] . '">' . $comprobante["total_opgravadas"] . '</cbc:LineExtensionAmount>
                <cbc:TaxInclusiveAmount currencyID="' . $comprobante["moneda"] . '">' . $comprobante["total"] . '</cbc:TaxInclusiveAmount>
                <cbc:PayableAmount currencyID="' . $comprobante["moneda"] . '">' . $comprobante["total"] . '</cbc:PayableAmount>
            </cac:LegalMonetaryTotal>';

            foreach ($modelos as $k => $v) {

                $igv = 0.18 * $v["total"];
                $totalIGV = $v["total"] + $igv;
                $precioIGV  = $totalIGV / $v["cantidad"];

                $xml .= '<cac:InvoiceLine>
                <cbc:ID>' . ($k + 1) . '</cbc:ID>
                <cbc:Note>' . $v["unidad"] . '</cbc:Note>
                <cbc:InvoicedQuantity unitCode="' . $v["unidad"] . '" unitCodeListAgencyName="United Nations Economic Commission for Europe"
                    unitCodeListID="UN/ECE rec 20">' . number_format($v["cantidad"], 3, ".", "") . '</cbc:InvoicedQuantity>
                <cbc:LineExtensionAmount currencyID="' . $comprobante["moneda"] . '">' . $v["total"] . '</cbc:LineExtensionAmount>
                <cac:BillingReference>
                    <cac:BillingReferenceLine>
                        <cbc:ID schemeID="AL">37.15</cbc:ID>
                    </cac:BillingReferenceLine>
                </cac:BillingReference>
                <cac:PricingReference>
                    <cac:AlternativeConditionPrice>
                        <cbc:PriceAmount currencyID="' . $comprobante["moneda"] . '">' . number_format($precioIGV, 2, ".", "") . '</cbc:PriceAmount>
                        <cbc:PriceTypeCode listAgencyName="PE:SUNAT" listName="Tipo de Precio"
                            listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo16">01</cbc:PriceTypeCode>
                    </cac:AlternativeConditionPrice>
                </cac:PricingReference>
                <cac:TaxTotal>
                    <cbc:TaxAmount currencyID="' . $comprobante["moneda"] . '">' . number_format($igv, 2, ".", "") . '</cbc:TaxAmount>
                    <cac:TaxSubtotal>
                        <cbc:TaxableAmount currencyID="' . $comprobante["moneda"] . '">' . $v["total"] . '</cbc:TaxableAmount>
                        <cbc:TaxAmount currencyID="' . $comprobante["moneda"] . '">' . number_format($igv, 2, ".", "") . '</cbc:TaxAmount>
                        <cac:TaxCategory>
                            <cbc:Percent>18</cbc:Percent>
                            <cbc:TaxExemptionReasonCode listAgencyName="PE:SUNAT" listName="Afectacion del IGV"
                                listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo07">10</cbc:TaxExemptionReasonCode>
                            <cac:TaxScheme>
                                <cbc:ID schemeAgencyName="PE:SUNAT" schemeName="Codigo de tributos"
                                    schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo05">1000</cbc:ID>
                                <cbc:Name>IGV</cbc:Name>
                                <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                            </cac:TaxScheme>
                        </cac:TaxCategory>
                    </cac:TaxSubtotal>
                </cac:TaxTotal>
                <cac:Item>
                    <cbc:Description>' . $v["nombre"] . '</cbc:Description>
                    <cac:SellersItemIdentification>
                        <cbc:ID>' . $v["modelo"] . '</cbc:ID>
                    </cac:SellersItemIdentification>
                </cac:Item>
                <cac:Price>
                    <cbc:PriceAmount currencyID="' . $comprobante["moneda"] . '">' . $v["precio"] . '</cbc:PriceAmount>
                </cac:Price>
            </cac:InvoiceLine>';
            }

            $xml .= "</Invoice>";




            $doc->loadXML($xml);
            $doc->save($ruta . '.XML');

            //CREAR XML FIRMA

            $objfirma = new Signature();

            // $ruta_xml_firmar = $ruta . '.XML'; //es el archivo XML que se va a firmar
            $ruta = $ruta . '.XML';
            $rutacertificado = "vistas/generar_xml/";

            $ruta_firma = $rutacertificado . 'certificado_prueba.pfx'; //ruta del archivo del certicado para firmar
            $pass_firma = 'ceti';
            // $actualizadoEnvio = ModeloFacturacion::mdlActualizarProcesoFacturacion(2,$tipo,$documento);
            $resp = $objfirma->signature_xml($flg_firma, $ruta, $ruta_firma, $pass_firma);

            echo '<script>

                            swal({
                                    type: "success",
                                    title: "Se Genero el  XML Invoice de ' . $venta["documento"] . '",
                                    showConfirmButton: true,
                                    confirmButtonText: "Cerrar"
                            }).then(function(result){
                                            if (result.value) {

                                            window.location = "procesar-ce";

                                            }
                                        })

                            </script>';
        }
    }

    static public function ctrCrearNotaCreditoXML()
    {

        if (isset($_GET["tipoNotaCred"]) && isset($_GET["documentoNotaCred"])) {

            $doc = new DOMDocument();
            $doc->formatOutput = FALSE;
            $doc->preserveWhiteSpace = TRUE;
            $doc->encoding = 'utf-8';

            require_once("/../extensiones/cantidad_en_letras.php");
            require_once("/../vistas/generar_xml/signature.php"); // permite firmar xml

            $tipo = $_GET["tipoNotaCred"];

            $documento = $_GET["documentoNotaCred"];

            $inicialOrigen = substr($venta["doc_origen"], 0, 1);

            if ($inicialOrigen == 'B') {
                $tipoOrigen = '03';
            } else {
                $tipoOrigen = '01';
            }

            $venta = ControladorFacturacion::ctrMostrarVentaImpresion($documento, $tipo);

            // var_dump($modelos);
            $emisor =     array(
                'tipodoc'        => '6',
                'ruc'             => '20513613939',
                'nombre_comercial' => 'JACKY FORM',
                'razon_social'    => 'Corporacion Vasco S.A.C.',
                'referencia'    => 'URB.SANTA LUISA 1RA ETAPA',
                'direccion'        => 'CAL.SANTO TORIBIO NRO. 259',
                'pais'            => 'PE',
                'departamento'  => 'LIMA',
                'provincia'        => 'LIMA',
                'distrito'        => 'SAN MARTIN DE PORRES'
            );


            $cliente = array(
                'tipodoc'        => '6', //6->ruc, 1-> dni 
                'ruc'            => $venta["dni"],
                'razon_social'  => $venta["nombre"],
                'cliente'       => $venta["cliente"],
                'direccion'        => $venta["direccion"],
                'pais'            => 'PE'
            );

            $vendedor = array(
                "codigo"        => $venta["vendedor"],
                "nombre"        => $venta["nom_vendedor"]
            );

            $comprobante =    array(
                'tipodoc'        => '07', //01->FACTURA, 03->BOLETA, 07->NC, 08->ND
                'serie'            => substr($venta["documento"], 0, 4),
                'correlativo'    => substr($venta["documento"], 4, 12),
                'fecha_emision' => $venta["fecha_emision"],
                'moneda'        => 'PEN', //PEN->SOLES; USD->DOLARES
                'total_opgravadas' => 0, //OP. GRAVADAS
                'total_opexoneradas' => 0,
                'total_opinafectas' => 0,
                'igv'            => 0,
                'total'            => 0,
                'total_texto'    => ''
            );



            $op_gravadas = 0;
            $op_inafectas = 0;
            $op_exoneradas = 0;

            $comprobante['total_opgravadas'] = ($venta["neto"] * -1);
            $comprobante['total_opexoneradas'] = $op_exoneradas;
            $comprobante['total_opinafectas'] = $op_inafectas;
            $comprobante['igv'] = ($venta["igv"] * -1);
            $comprobante['total'] = ($venta["total"] * -1);
            $comprobante['total_texto'] = CantidadEnLetra($venta["total"] * -1);
            $totalSinIGV = ($venta["total"] * -1) - ($venta["igv"] * -1);

            $serieInvoice = substr($venta["doc_origen"], 0, 4);
            $correlativoInvoice = substr($venta["doc_origen"], 4, 12);

            //RUC DEL EMISOR - TIPO DE COMPROBANTE - SERIE DEL DOCUMENTO - CORRELATIVO
            //01-> FACTURA, 03-> BOLETA, 07-> NOTA DE CREDITO, 08-> NOTA DE DEBITO, 09->GUIA DE REMISION
            $nombrexml = $emisor['ruc'] . '-' . $comprobante['tipodoc'] . '-' . $comprobante['serie'] . '-' . $comprobante['correlativo'];

            $ruta = "vistas/generar_xml/archivos_xml/" . $nombrexml;

            $tipoCliente = $cliente["ruc"];

            if (strlen($tipoCliente) == 8) {
                $tipodoc = '1';
            } else {
                $tipodoc = '6';
            }

            //TIPO DE MOTIVO SEGUN SUNAT
            if ($venta["motivo"] == "C1") {

                $tipoMotivo = "01";
            } else if ($venta["motivo"] == "C2") {

                $tipoMotivo = "02";
            } else if ($venta["motivo"] == "C3") {

                $tipoMotivo = "03";
            } else if ($venta["motivo"] == "C4") {

                $tipoMotivo = "04";
            } else if ($venta["motivo"] == "C5") {

                $tipoMotivo = "05";
            } else if ($venta["motivo"] == "C6") {

                $tipoMotivo = "06";
            } else if ($venta["motivo"] == "C7") {

                $tipoMotivo = "07";
            } else if ($venta["motivo"] == "C8") {

                $tipoMotivo = "08";
            } else if ($venta["motivo"] == "C9") {

                $tipoMotivo = "09";
            } else {

                $tipoMotivo = "10";
            }

            if ($comprobante["serie"] == "F002" || $comprobante["serie"] == "B002") {

                $xml = '<?xml version="1.0" encoding="UTF-8"?>
                <CreditNote xmlns="urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2">
                <ext:UBLExtensions>
                    <ext:UBLExtension>
                        <ext:ExtensionContent />
                    </ext:UBLExtension>
                </ext:UBLExtensions>
                    <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
                    <cbc:CustomizationID>2.0</cbc:CustomizationID>
                    <cbc:ID>' . $comprobante['serie'] . '-' . $comprobante['correlativo'] . '</cbc:ID>
                    <cbc:IssueDate>' . $comprobante['fecha_emision'] . '</cbc:IssueDate>
                    <cbc:Note languageLocaleID="1000"> ' . $comprobante["total_texto"] . '</cbc:Note>
                    <cbc:Note languageID="D">' . $cliente["cliente"] . '</cbc:Note>
                    <cbc:Note languageID="F">' . $totalSinIGV . '</cbc:Note>
                    <cbc:Note languageID="G">' . $vendedor["codigo"] . ' ' . $vendedor["nombre"] . '</cbc:Note>
                    <cbc:DocumentCurrencyCode listAgencyName="United Nations Economic Commission for Europe" listID="ISO 4217 Alpha" listName="Currency">PEN</cbc:DocumentCurrencyCode>
                    <cbc:LineCountNumeric>1</cbc:LineCountNumeric>
                    <cac:DiscrepancyResponse>
                        <cbc:ReferenceID>' . $serieInvoice . "-" . $correlativoInvoice . '</cbc:ReferenceID>
                        <cbc:ResponseCode listAgencyName="PE:SUNAT" listName="Tipo de nota de credito" listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo09">' . $tipoMotivo . '</cbc:ResponseCode>
                        <cbc:Description>' . ucfirst(strtolower($venta['nom_motivo'])) . '</cbc:Description>
                    </cac:DiscrepancyResponse>
                    <cac:BillingReference>
                        <cac:InvoiceDocumentReference>
                            <cbc:ID>' . $serieInvoice . "-" . $correlativoInvoice . '</cbc:ID>
                            <cbc:IssueDate>' . $venta["fecha_origen"] . '</cbc:IssueDate>
                            <cbc:DocumentTypeCode listAgencyName="PE:SUNAT" listName="Tipo de Documento" listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo01">' . $tipoOrigen . '</cbc:DocumentTypeCode>
                        </cac:InvoiceDocumentReference>
                    </cac:BillingReference>
                    <cac:Signature>
                        <cbc:ID>IDSignKG</cbc:ID>
                        <cac:SignatoryParty>
                            <cac:PartyIdentification>
                                <cbc:ID>' . $emisor["ruc"] . '</cbc:ID>
                            </cac:PartyIdentification>
                            <cac:PartyName>
                                <cbc:Name>' . $emisor["razon_social"] . '</cbc:Name>
                            </cac:PartyName>
                        </cac:SignatoryParty>
                        <cac:DigitalSignatureAttachment>
                            <cac:ExternalReference>
                                <cbc:URI>#SignST</cbc:URI>
                            </cac:ExternalReference>
                        </cac:DigitalSignatureAttachment>
                    </cac:Signature>
                    <cac:AccountingSupplierParty>
                        <cac:Party>
                            <cac:PartyIdentification>
                                <cbc:ID schemeAgencyName="PE:SUNAT" schemeID="6" schemeName="Documento de Identidad" schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">' . $emisor["ruc"] . '</cbc:ID>
                            </cac:PartyIdentification>
                            <cac:PartyName>
                                <cbc:Name>' . $emisor["nombre_comercial"] . '</cbc:Name>
                            </cac:PartyName>
                            <cac:PartyLegalEntity>
                                <cbc:RegistrationName>' . $emisor["razon_social"] . '</cbc:RegistrationName>
                                <cac:RegistrationAddress>
                                    <cbc:AddressTypeCode listAgencyName="PE:SUNAT" listName="Establecimientos anexos">0002
                                    </cbc:AddressTypeCode>
                                    <cbc:CitySubdivisionName>' . $emisor["referencia"] . '</cbc:CitySubdivisionName>
                                    <cbc:CityName>' . $emisor["provincia"] . '</cbc:CityName>
                                    <cbc:CountrySubentity>' . $emisor["departamento"] . '</cbc:CountrySubentity>
                                    <cbc:District>' . $emisor["distrito"] . '</cbc:District>
                                    <cac:AddressLine>
                                        <cbc:Line>' . $emisor["direccion"] . '</cbc:Line>
                                    </cac:AddressLine>
                                    <cac:Country>
                                        <cbc:IdentificationCode listAgencyName="United Nations Economic Commission for Europe" listID="ISO 3166-1" listName="Country">' . $emisor["pais"] . '</cbc:IdentificationCode>
                                    </cac:Country>
                                </cac:RegistrationAddress>
                            </cac:PartyLegalEntity>
                        </cac:Party>
                    </cac:AccountingSupplierParty>
                    <cac:AccountingCustomerParty>
                        <cac:Party>
                        <cac:PartyIdentification>
                            <cbc:ID schemeAgencyName="PE:SUNAT" schemeID="' . $tipodoc . '" schemeName="Documento de Identidad"
                            schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">' . $cliente["ruc"] . '</cbc:ID>
                        </cac:PartyIdentification>
                        <cac:PartyLegalEntity>
                        <cbc:RegistrationName>' . $cliente["razon_social"] . '</cbc:RegistrationName>
                        <cac:RegistrationAddress>
                            <cbc:ID schemeAgencyName="PE:INEI" schemeName="Ubigeos" />
                            <cbc:CitySubdivisionName>-</cbc:CitySubdivisionName>
                            <cbc:CityName />
                            <cbc:CountrySubentity>' . $venta["departamento"] . '</cbc:CountrySubentity>
                            <cbc:District />
                            <cac:AddressLine>
                                <cbc:Line>' . $cliente["direccion"] . '</cbc:Line>
                            </cac:AddressLine>
                            <cac:Country>
                                <cbc:IdentificationCode listAgencyName="United Nations Economic Commission for Europe" listID="ISO 3166-1" listName="Country">' . $cliente["pais"] . '</cbc:IdentificationCode>
                            </cac:Country>
                        </cac:RegistrationAddress>
                        </cac:PartyLegalEntity>
                        <cac:Contact>
                            <cbc:ElectronicMail>' . $venta["email"] . '</cbc:ElectronicMail>
                        </cac:Contact>
                        </cac:Party>
                    </cac:AccountingCustomerParty>
                    <cac:TaxTotal>
                        <cbc:TaxAmount currencyID="' . $comprobante['moneda'] . '">' . $comprobante['igv'] . '</cbc:TaxAmount>
                        <cac:TaxSubtotal>
                        <cbc:TaxableAmount currencyID="' . $comprobante['moneda'] . '">' . $comprobante["total_opgravadas"] . '</cbc:TaxableAmount>
                        <cbc:TaxAmount currencyID="' . $comprobante['moneda'] . '">' . $comprobante['igv'] . '</cbc:TaxAmount>
                        <cac:TaxCategory>
                            <cac:TaxScheme>
                                <cbc:ID schemeAgencyID="6" schemeID="UN/ECE 5153">1000</cbc:ID>
                                <cbc:Name>IGV</cbc:Name>
                                <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                            </cac:TaxScheme>
                        </cac:TaxCategory>
                        </cac:TaxSubtotal>
                    </cac:TaxTotal>
                    <cac:LegalMonetaryTotal>
                        <cbc:PayableAmount currencyID="' . $comprobante['moneda'] . '">' . $comprobante['total'] . '</cbc:PayableAmount>
                    </cac:LegalMonetaryTotal>
                    <cac:CreditNoteLine>
                        <cbc:ID>1</cbc:ID>
                        <cbc:Note>ZZ</cbc:Note>
                        <cbc:CreditedQuantity unitCode="ZZ" unitCodeListAgencyName="United Nations Economic Commission for Europe" unitCodeListID="UN/ECE rec 20">1</cbc:CreditedQuantity>
                        <cbc:LineExtensionAmount currencyID="PEN">' . $comprobante["total_opgravadas"] . '</cbc:LineExtensionAmount>
                        <cac:BillingReference>
                            <cac:BillingReferenceLine>
                                <cbc:ID schemeID="AF">' . $comprobante["total"] . '</cbc:ID>
                            </cac:BillingReferenceLine>
                        </cac:BillingReference>
                        <cac:PricingReference>
                            <cac:AlternativeConditionPrice>
                                <cbc:PriceAmount currencyID="PEN">' . $comprobante["total"] . '</cbc:PriceAmount>
                                <cbc:PriceTypeCode listAgencyName="PE:SUNAT" listName="Tipo de Precio" listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo16">01</cbc:PriceTypeCode>
                            </cac:AlternativeConditionPrice>
                        </cac:PricingReference>
                        <cac:TaxTotal>
                            <cbc:TaxAmount currencyID="PEN">' . $comprobante["igv"] . '</cbc:TaxAmount>
                            <cac:TaxSubtotal>
                            <cbc:TaxableAmount currencyID="PEN">' . $comprobante["total_opgravadas"] . '</cbc:TaxableAmount>
                            <cbc:TaxAmount currencyID="PEN">' . $comprobante["igv"] . '</cbc:TaxAmount>
                            <cac:TaxCategory>
                                <cbc:Percent>18.00</cbc:Percent>
                                <cbc:TaxExemptionReasonCode listAgencyName="PE:SUNAT" listName="Afectacion del IGV" listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo07">10</cbc:TaxExemptionReasonCode>
                                <cac:TaxScheme>
                                    <cbc:ID schemeAgencyName="PE:SUNAT" schemeName="Codigo de tributos" schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo05">1000</cbc:ID>
                                    <cbc:Name>IGV</cbc:Name>
                                    <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                                </cac:TaxScheme>
                            </cac:TaxCategory>
                            </cac:TaxSubtotal>
                        </cac:TaxTotal>
                        <cac:Item>
                            <cbc:Description>' . $venta["observacion"] . '</cbc:Description>
                        </cac:Item>
                        <cac:Price>
                            <cbc:PriceAmount currencyID="PEN">' . $comprobante["total_opgravadas"] . '</cbc:PriceAmount>
                        </cac:Price>
                    </cac:CreditNoteLine>
                </CreditNote>';
            } else {
                $modelos = ControladorFacturacion::ctrMostrarModeloImpresion($documento, $tipo);

                $unidad = ControladorFacturacion::ctrMostrarUnidadesImpresion($documento, $tipo);


                $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <CreditNote xmlns="urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2">
            <ext:UBLExtensions>
                <ext:UBLExtension>
                    <ext:ExtensionContent />
                </ext:UBLExtension>
            </ext:UBLExtensions>
            <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
            <cbc:CustomizationID>2.0</cbc:CustomizationID>
            <cbc:ID>' . $comprobante["serie"] . '-' . $comprobante["correlativo"] . '</cbc:ID>
            <cbc:IssueDate>' . $comprobante["fecha_emision"] . '</cbc:IssueDate>
            <cbc:Note languageLocaleID="1000"> ' . $comprobante["total_texto"] . '</cbc:Note>
            <cbc:Note>Nro.unidades: ' . ($unidad["cantidad"] * -1) . '</cbc:Note>
            <cbc:Note languageID="D">' . $cliente["cliente"] . '</cbc:Note>
            <cbc:Note languageID="E">CONTADO .</cbc:Note>
            <cbc:Note languageID="F">' . $totalSinIGV . '</cbc:Note>
            <cbc:Note languageID="G">' . $vendedor["codigo"] . ' ' . $vendedor["nombre"] . '</cbc:Note>
            <cbc:DocumentCurrencyCode listAgencyName="United Nations Economic Commission for Europe" listID="ISO 4217 Alpha"
                listName="Currency">PEN</cbc:DocumentCurrencyCode>
            <cbc:LineCountNumeric>' . count($modelos) . '</cbc:LineCountNumeric>
            <cac:DiscrepancyResponse>
                <cbc:ReferenceID>' . $serieInvoice . "-" . $correlativoInvoice . '</cbc:ReferenceID>
                <cbc:ResponseCode listAgencyName="PE:SUNAT" listName="Tipo de nota de credito" listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo09">' . $tipoMotivo . '</cbc:ResponseCode>
                <cbc:Description>' . ucfirst(strtolower($venta['nom_motivo'])) . '</cbc:Description>
            </cac:DiscrepancyResponse>
            <cac:OrderReference>
                <cbc:ID>' . $venta["origen2"] . '</cbc:ID>
            </cac:OrderReference>
            <cac:BillingReference>
                <cac:InvoiceDocumentReference>
                    <cbc:ID>' . $serieInvoice . "-" . $correlativoInvoice . '</cbc:ID>
                    <cbc:IssueDate>' . $venta["fecha_origen"] . '</cbc:IssueDate>
                    <cbc:DocumentTypeCode listAgencyName="PE:SUNAT" listName="Tipo de Documento" listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo01">' . $tipoOrigen . '</cbc:DocumentTypeCode>
                </cac:InvoiceDocumentReference>
            </cac:BillingReference>
            <cac:Signature>
                <cbc:ID>IDSignKG</cbc:ID>
                <cac:SignatoryParty>
                    <cac:PartyIdentification>
                        <cbc:ID>' . $emisor["ruc"] . '</cbc:ID>
                    </cac:PartyIdentification>
                    <cac:PartyName>
                        <cbc:Name>' . $emisor["razon_social"] . '</cbc:Name>
                    </cac:PartyName>
                </cac:SignatoryParty>
                <cac:DigitalSignatureAttachment>
                    <cac:ExternalReference>
                        <cbc:URI>#SignST</cbc:URI>
                    </cac:ExternalReference>
                </cac:DigitalSignatureAttachment>
            </cac:Signature>
            <cac:AccountingSupplierParty>
                <cac:Party>
                    <cac:PartyIdentification>
                        <cbc:ID schemeAgencyName="PE:SUNAT" schemeID="6" schemeName="Documento de Identidad"
                            schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">' . $emisor["ruc"] . '</cbc:ID>
                    </cac:PartyIdentification>
                    <cac:PartyName>
                        <cbc:Name>' . $emisor["nombre_comercial"] . '</cbc:Name>
                    </cac:PartyName>
                    <cac:PartyLegalEntity>
                        <cbc:RegistrationName>' . $emisor["razon_social"] . '</cbc:RegistrationName>
                        <cac:RegistrationAddress>
                            <cbc:AddressTypeCode listAgencyName="PE:SUNAT" listName="Establecimientos anexos">0002
                            </cbc:AddressTypeCode>
                            <cbc:CitySubdivisionName>' . $emisor["referencia"] . '</cbc:CitySubdivisionName>
                            <cbc:CityName>' . $emisor["provincia"] . '</cbc:CityName>
                            <cbc:CountrySubentity>' . $emisor["departamento"] . '</cbc:CountrySubentity>
                            <cbc:District>' . $emisor["distrito"] . '</cbc:District>
                            <cac:AddressLine>
                                <cbc:Line>' . $emisor["direccion"] . '</cbc:Line>
                            </cac:AddressLine>
                            <cac:Country>
                                <cbc:IdentificationCode listAgencyName="United Nations Economic Commission for Europe"
                                    listID="ISO 3166-1" listName="Country">' . $emisor["pais"] . '</cbc:IdentificationCode>
                            </cac:Country>
                        </cac:RegistrationAddress>
                    </cac:PartyLegalEntity>
                </cac:Party>
            </cac:AccountingSupplierParty>
            <cac:AccountingCustomerParty>
                <cac:Party>
                    <cac:PartyIdentification>
                        <cbc:ID schemeAgencyName="PE:SUNAT" schemeID="' . $tipodoc . '" schemeName="Documento de Identidad"
                            schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">' . $cliente["ruc"] . '</cbc:ID>
                    </cac:PartyIdentification>
                    <cac:PartyName>
                        <cbc:Name />
                    </cac:PartyName>
                    <cac:PartyLegalEntity>
                        <cbc:RegistrationName>' . $cliente["razon_social"] . '</cbc:RegistrationName>
                        <cac:RegistrationAddress>
                            <cbc:ID schemeAgencyName="PE:INEI" schemeName="Ubigeos" />
                            <cbc:CitySubdivisionName>-</cbc:CitySubdivisionName>
                            <cbc:CityName />
                            <cbc:CountrySubentity>' . $venta["departamento"] . '</cbc:CountrySubentity>
                            <cbc:District />
                            <cac:AddressLine>
                                <cbc:Line>' . $cliente["direccion"] . '</cbc:Line>
                            </cac:AddressLine>
                            <cac:Country>
                                <cbc:IdentificationCode listAgencyName="United Nations Economic Commission for Europe"
                                    listID="ISO 3166-1" listName="Country">' . $cliente["pais"] . '</cbc:IdentificationCode>
                            </cac:Country>
                        </cac:RegistrationAddress>
                    </cac:PartyLegalEntity>
                    <cac:Contact>
                    <cbc:ElectronicMail>' . $venta["email"] . '</cbc:ElectronicMail>
                    </cac:Contact>
                </cac:Party>
            </cac:AccountingCustomerParty>
            <cac:TaxTotal>
                <cbc:TaxAmount currencyID="' . $comprobante["moneda"] . '">' . $comprobante["igv"] . '</cbc:TaxAmount>
                <cac:TaxSubtotal>
                    <cbc:TaxableAmount currencyID="' . $comprobante["moneda"] . '">' . $comprobante["total_opgravadas"] . '</cbc:TaxableAmount>
                    <cbc:TaxAmount currencyID="' . $comprobante["moneda"] . '">' . $comprobante["igv"] . '</cbc:TaxAmount>
                    <cac:TaxCategory>
                        <cac:TaxScheme>
                            <cbc:ID schemeAgencyID="6" schemeID="UN/ECE 5153">1000</cbc:ID>
                            <cbc:Name>IGV</cbc:Name>
                            <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                        </cac:TaxScheme>
                    </cac:TaxCategory>
                </cac:TaxSubtotal>';
                if ($venta["dscto"] < 0) {
                    $xml .= '<cac:TaxSubtotal>
                        <cbc:TaxAmount currencyID="PEN">' . ($venta["dscto"] * -1) . '</cbc:TaxAmount>
                        <cac:TaxCategory>
                            <cac:TaxScheme>
                                <cbc:ID schemeAgencyID="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo05" schemeAgencyName="PE:SUNAT" schemeName="Codigo de tributos">7152</cbc:ID>
                                <cbc:Name>ICBPER</cbc:Name>
                                <cbc:TaxTypeCode>OTH</cbc:TaxTypeCode>
                            </cac:TaxScheme>
                        </cac:TaxCategory>
                    </cac:TaxSubtotal>';
                }
                $xml .= '</cac:TaxTotal>
            <cac:LegalMonetaryTotal>
                <cbc:PayableAmount currencyID="' . $comprobante["moneda"] . '">' . $comprobante["total"] . '</cbc:PayableAmount>
            </cac:LegalMonetaryTotal>';

                foreach ($modelos as $k => $v) {

                    $igv = 0.18 * ($v["total"] * -1);
                    $totalIGV = ($v["total"] * -1) + $igv;
                    $precioIGV  = $totalIGV / ($v["cantidad"] * -1);

                    $xml .= '<cac:CreditNoteLine>
                <cbc:ID>' . ($k + 1) . '</cbc:ID>
                <cbc:Note>' . $v["unidad"] . '</cbc:Note>
                <cbc:InvoicedQuantity unitCode="' . $v["unidad"] . '" unitCodeListAgencyName="United Nations Economic Commission for Europe"
                    unitCodeListID="UN/ECE rec 20">' . number_format(($v["cantidad"] * -1), 3, ".", "") . '</cbc:InvoicedQuantity>
                <cbc:LineExtensionAmount currencyID="' . $comprobante["moneda"] . '">' . ($v["total"] * -1) . '</cbc:LineExtensionAmount>
                <cac:BillingReference>
                    <cac:BillingReferenceLine>
                        <cbc:ID schemeID="AL">37.15</cbc:ID>
                    </cac:BillingReferenceLine>
                </cac:BillingReference>
                <cac:PricingReference>
                    <cac:AlternativeConditionPrice>
                        <cbc:PriceAmount currencyID="' . $comprobante["moneda"] . '">' . number_format($precioIGV, 2, ".", "") . '</cbc:PriceAmount>
                        <cbc:PriceTypeCode listAgencyName="PE:SUNAT" listName="Tipo de Precio"
                            listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo16">01</cbc:PriceTypeCode>
                    </cac:AlternativeConditionPrice>
                </cac:PricingReference>
                <cac:TaxTotal>
                    <cbc:TaxAmount currencyID="' . $comprobante["moneda"] . '">' . number_format($igv, 2, ".", "") . '</cbc:TaxAmount>
                    <cac:TaxSubtotal>
                        <cbc:TaxableAmount currencyID="' . $comprobante["moneda"] . '">' . ($v["total"] * -1) . '</cbc:TaxableAmount>
                        <cbc:TaxAmount currencyID="' . $comprobante["moneda"] . '">' . number_format($igv, 2, ".", "") . '</cbc:TaxAmount>
                        <cac:TaxCategory>
                            <cbc:Percent>18</cbc:Percent>
                            <cbc:TaxExemptionReasonCode listAgencyName="PE:SUNAT" listName="Afectacion del IGV"
                                listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo07">10</cbc:TaxExemptionReasonCode>
                            <cac:TaxScheme>
                                <cbc:ID schemeAgencyName="PE:SUNAT" schemeName="Codigo de tributos"
                                    schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo05">1000</cbc:ID>
                                <cbc:Name>IGV</cbc:Name>
                                <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                            </cac:TaxScheme>
                        </cac:TaxCategory>
                    </cac:TaxSubtotal>
                </cac:TaxTotal>
                <cac:Item>
                    <cbc:Description>' . $v["nombre"] . '</cbc:Description>
                    <cac:SellersItemIdentification>
                        <cbc:ID>' . $v["modelo"] . '</cbc:ID>
                    </cac:SellersItemIdentification>
                </cac:Item>
                <cac:Price>
                    <cbc:PriceAmount currencyID="' . $comprobante["moneda"] . '">' . $v["precio"] . '</cbc:PriceAmount>
                </cac:Price>
            </cac:CreditNoteLine>';
                }


                $xml .= "</CreditNote>";
            }

            $doc->loadXML($xml);
            $doc->save($ruta . '.XML');

            //CREAR XML FIRMA

            $objfirma = new Signature();

            // $ruta_xml_firmar = $ruta . '.XML'; //es el archivo XML que se va a firmar
            $ruta = $ruta . '.XML';
            $rutacertificado = "vistas/generar_xml/";
            $flg_firma = 0; //Posicion del XML: 0 para firma
            $ruta_firma = $rutacertificado . 'certificado_prueba.pfx'; //ruta del archivo del certicado para firmar
            $pass_firma = 'ceti';
            $actualizadoEnvio = ModeloFacturacion::mdlActualizarProcesoFacturacion(2, $tipo, $documento);
            $resp = $objfirma->signature_xml($flg_firma, $ruta, $ruta_firma, $pass_firma);

            echo '<script>

                            swal({
                                    type: "success",
                                    title: "Se Genero el XML Nota de Credito de ' . $venta["documento"] . '",
                                    showConfirmButton: true,
                                    confirmButtonText: "Cerrar"
                            }).then(function(result){
                                            if (result.value) {

                                            window.location = "procesar-ce";

                                            }
                                        })

                            </script>';
        }
    }

    static public function ctrCrearNotaDebitoXML()
    {

        if (isset($_GET["tipoNotaDeb"]) && isset($_GET["documentoNotaDeb"])) {

            $doc = new DOMDocument();
            $doc->formatOutput = FALSE;
            $doc->preserveWhiteSpace = TRUE;
            $doc->encoding = 'utf-8';

            require_once("/../extensiones/cantidad_en_letras.php");
            require_once("/../vistas/generar_xml/signature.php"); // permite firmar xml

            $tipo = $_GET["tipoNotaDeb"];

            $documento = $_GET["documentoNotaDeb"];

            $inicialOrigen = substr($venta["doc_origen"], 0, 1);

            if ($inicialOrigen == 'B') {
                $tipoOrigen = '03';
            } else {
                $tipoOrigen = '01';
            }

            $venta = ControladorFacturacion::ctrMostrarDebitoImpresion($documento, $tipo);

            // var_dump($modelos);
            $emisor =     array(
                'tipodoc'        => '6',
                'ruc'             => '20513613939',
                'nombre_comercial' => 'JACKY FORM',
                'razon_social'    => 'Corporacion Vasco S.A.C.',
                'referencia'    => 'URB.SANTA LUISA 1RA ETAPA',
                'direccion'        => 'CAL.SANTO TORIBIO NRO. 259',
                'pais'            => 'PE',
                'departamento'  => 'LIMA',
                'provincia'        => 'LIMA',
                'distrito'        => 'SAN MARTIN DE PORRES'
            );


            $cliente = array(
                'tipodoc'        => '6', //6->ruc, 1-> dni 
                'ruc'            => $venta["dni"],
                'razon_social'  => $venta["nombre"],
                'cliente'       => $venta["cliente"],
                'direccion'        => $venta["direccion"],
                'pais'            => 'PE'
            );

            $vendedor = array(
                "codigo"        => $venta["vendedor"],
                "nombre"        => $venta["nom_vendedor"]
            );

            $comprobante =    array(
                'tipodoc'        => '08', //01->FACTURA, 03->BOLETA, 07->NC, 08->ND
                'serie'            => substr($venta["documento"], 0, 4),
                'correlativo'    => substr($venta["documento"], 4, 12),
                'fecha_emision' => $venta["fecha_emision"],
                'moneda'        => 'PEN', //PEN->SOLES; USD->DOLARES
                'total_opgravadas' => 0, //OP. GRAVADAS
                'total_opexoneradas' => 0,
                'total_opinafectas' => 0,
                'igv'            => 0,
                'total'            => 0,
                'total_texto'    => ''
            );



            $op_gravadas = 0;
            $op_inafectas = 0;
            $op_exoneradas = 0;

            $comprobante['total_opgravadas'] = $venta["neto"];
            $comprobante['total_opexoneradas'] = $op_exoneradas;
            $comprobante['total_opinafectas'] = $op_inafectas;
            $comprobante['igv'] = $venta["igv"];
            $comprobante['total'] = $venta["total"];
            $comprobante['total_texto'] = CantidadEnLetra($venta["total"]);
            $totalSinIGV = $venta["total"] - $venta["igv"];

            $serieInvoice = substr($venta["doc_origen"], 0, 4);
            $correlativoInvoice = substr($venta["doc_origen"], 4, 12);

            //RUC DEL EMISOR - TIPO DE COMPROBANTE - SERIE DEL DOCUMENTO - CORRELATIVO
            //01-> FACTURA, 03-> BOLETA, 07-> NOTA DE CREDITO, 08-> NOTA DE DEBITO, 09->GUIA DE REMISION
            $nombrexml = $emisor['ruc'] . '-' . $comprobante['tipodoc'] . '-' . $comprobante['serie'] . '-' . $comprobante['correlativo'];

            $ruta = "vistas/generar_xml/archivos_xml/" . $nombrexml;

            $tipoCliente = $cliente["ruc"];

            if (strlen($tipoCliente) == 8) {
                $tipodoc = '1';
            } else {
                $tipodoc = '6';
            }

            //TIPO DE MOTIVO SEGUN SUNAT
            if ($venta["motivo"] == "D1") {

                $tipoMotivo = "01";
            } else if ($venta["motivo"] == "D2") {

                $tipoMotivo = "02";
            } else {

                $tipoMotivo = "03";
            }



            $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <DebitNote xmlns="urn:oasis:names:specification:ubl:schema:xsd:DebitNote-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2">
            <ext:UBLExtensions>
                <ext:UBLExtension>
                    <ext:ExtensionContent />
                </ext:UBLExtension>
            </ext:UBLExtensions>
                <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
                <cbc:CustomizationID>2.0</cbc:CustomizationID>
                <cbc:ID>' . $comprobante['serie'] . '-' . $comprobante['correlativo'] . '</cbc:ID>
                <cbc:IssueDate>' . $comprobante['fecha_emision'] . '</cbc:IssueDate>
                <cbc:Note languageLocaleID="1000"> ' . $comprobante["total_texto"] . '</cbc:Note>
                <cbc:Note languageID="D">' . $cliente["cliente"] . '</cbc:Note>
                <cbc:Note languageID="F">' . $totalSinIGV . '</cbc:Note>
                <cbc:Note languageID="G">' . $vendedor["codigo"] . ' ' . $vendedor["nombre"] . '</cbc:Note>
                <cbc:DocumentCurrencyCode listAgencyName="United Nations Economic Commission for Europe" listID="ISO 4217 Alpha" listName="Currency">PEN</cbc:DocumentCurrencyCode>
                <cbc:LineCountNumeric>1</cbc:LineCountNumeric>
                <cac:DiscrepancyResponse>
                    <cbc:ReferenceID>' . $serieInvoice . "-" . $correlativoInvoice . '</cbc:ReferenceID>
                    <cbc:ResponseCode listAgencyName="PE:SUNAT" listName="Tipo de nota de credito" listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo09">' . $tipoMotivo . '</cbc:ResponseCode>
                    <cbc:Description>' . ucfirst(strtolower($venta['nom_motivo'])) . '</cbc:Description>
                </cac:DiscrepancyResponse>
                <cac:BillingReference>
                    <cac:InvoiceDocumentReference>
                        <cbc:ID>' . $serieInvoice . "-" . $correlativoInvoice . '</cbc:ID>
                        <cbc:IssueDate>' . $venta["fecha_origen"] . '</cbc:IssueDate>
                        <cbc:DocumentTypeCode listAgencyName="PE:SUNAT" listName="Tipo de Documento" listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo01">' . $tipoOrigen . '</cbc:DocumentTypeCode>
                    </cac:InvoiceDocumentReference>
                </cac:BillingReference>
                <cac:Signature>
                    <cbc:ID>IDSignKG</cbc:ID>
                    <cac:SignatoryParty>
                        <cac:PartyIdentification>
                            <cbc:ID>' . $emisor["ruc"] . '</cbc:ID>
                        </cac:PartyIdentification>
                        <cac:PartyName>
                            <cbc:Name>' . $emisor["razon_social"] . '</cbc:Name>
                        </cac:PartyName>
                    </cac:SignatoryParty>
                    <cac:DigitalSignatureAttachment>
                        <cac:ExternalReference>
                            <cbc:URI>#SignST</cbc:URI>
                        </cac:ExternalReference>
                    </cac:DigitalSignatureAttachment>
                </cac:Signature>
                <cac:AccountingSupplierParty>
                    <cac:Party>
                        <cac:PartyIdentification>
                            <cbc:ID schemeAgencyName="PE:SUNAT" schemeID="6" schemeName="Documento de Identidad" schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">' . $emisor["ruc"] . '</cbc:ID>
                        </cac:PartyIdentification>
                        <cac:PartyName>
                            <cbc:Name>' . $emisor["nombre_comercial"] . '</cbc:Name>
                        </cac:PartyName>
                        <cac:PartyLegalEntity>
                            <cbc:RegistrationName>' . $emisor["razon_social"] . '</cbc:RegistrationName>
                            <cac:RegistrationAddress>
                                <cbc:AddressTypeCode listAgencyName="PE:SUNAT" listName="Establecimientos anexos">0002
                                </cbc:AddressTypeCode>
                                <cbc:CitySubdivisionName>' . $emisor["referencia"] . '</cbc:CitySubdivisionName>
                                <cbc:CityName>' . $emisor["provincia"] . '</cbc:CityName>
                                <cbc:CountrySubentity>' . $emisor["departamento"] . '</cbc:CountrySubentity>
                                <cbc:District>' . $emisor["distrito"] . '</cbc:District>
                                <cac:AddressLine>
                                    <cbc:Line>' . $emisor["direccion"] . '</cbc:Line>
                                </cac:AddressLine>
                                <cac:Country>
                                    <cbc:IdentificationCode listAgencyName="United Nations Economic Commission for Europe" listID="ISO 3166-1" listName="Country">' . $emisor["pais"] . '</cbc:IdentificationCode>
                                </cac:Country>
                            </cac:RegistrationAddress>
                        </cac:PartyLegalEntity>
                    </cac:Party>
                </cac:AccountingSupplierParty>
                <cac:AccountingCustomerParty>
                    <cac:Party>
                    <cac:PartyIdentification>
                        <cbc:ID schemeAgencyName="PE:SUNAT" schemeID="' . $tipodoc . '" schemeName="Documento de Identidad"
                        schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">' . $cliente["ruc"] . '</cbc:ID>
                    </cac:PartyIdentification>
                    <cac:PartyLegalEntity>
                    <cbc:RegistrationName>' . $cliente["razon_social"] . '</cbc:RegistrationName>
                    <cac:RegistrationAddress>
                        <cbc:ID schemeAgencyName="PE:INEI" schemeName="Ubigeos" />
                        <cbc:CitySubdivisionName>-</cbc:CitySubdivisionName>
                        <cbc:CityName />
                        <cbc:CountrySubentity>' . $venta["departamento"] . '</cbc:CountrySubentity>
                        <cbc:District />
                        <cac:AddressLine>
                            <cbc:Line>' . $cliente["direccion"] . '</cbc:Line>
                        </cac:AddressLine>
                        <cac:Country>
                            <cbc:IdentificationCode listAgencyName="United Nations Economic Commission for Europe" listID="ISO 3166-1" listName="Country">' . $cliente["pais"] . '</cbc:IdentificationCode>
                        </cac:Country>
                    </cac:RegistrationAddress>
                    </cac:PartyLegalEntity>
                    <cac:Contact>
                        <cbc:ElectronicMail>' . $venta["email"] . '</cbc:ElectronicMail>
                    </cac:Contact>
                    </cac:Party>
                </cac:AccountingCustomerParty>
                <cac:TaxTotal>
                    <cbc:TaxAmount currencyID="' . $comprobante['moneda'] . '">' . $comprobante['igv'] . '</cbc:TaxAmount>
                    <cac:TaxSubtotal>
                    <cbc:TaxableAmount currencyID="' . $comprobante['moneda'] . '">' . $comprobante["total_opgravadas"] . '</cbc:TaxableAmount>
                    <cbc:TaxAmount currencyID="' . $comprobante['moneda'] . '">' . $comprobante['igv'] . '</cbc:TaxAmount>
                    <cac:TaxCategory>
                        <cac:TaxScheme>
                            <cbc:ID schemeAgencyID="6" schemeID="UN/ECE 5153">1000</cbc:ID>
                            <cbc:Name>IGV</cbc:Name>
                            <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                        </cac:TaxScheme>
                    </cac:TaxCategory>
                    </cac:TaxSubtotal>
                </cac:TaxTotal>
                <cac:RequestedMonetaryTotal>
                    <cbc:PayableAmount currencyID="' . $comprobante['moneda'] . '">' . $comprobante['total'] . '</cbc:PayableAmount>
                </cac:RequestedMonetaryTotal>
                <cac:DebitNoteLine>
                    <cbc:ID>1</cbc:ID>
                    <cbc:Note>ZZ</cbc:Note>
                    <cbc:DebitedQuantity unitCode="ZZ" unitCodeListAgencyName="United Nations Economic Commission for Europe" unitCodeListID="UN/ECE rec 20">1</cbc:DebitedQuantity>
                    <cbc:LineExtensionAmount currencyID="PEN">' . $comprobante["total_opgravadas"] . '</cbc:LineExtensionAmount>
                    <cac:BillingReference>
                        <cac:BillingReferenceLine>
                            <cbc:ID schemeID="AF">' . $comprobante["total"] . '</cbc:ID>
                        </cac:BillingReferenceLine>
                    </cac:BillingReference>
                    <cac:PricingReference>
                        <cac:AlternativeConditionPrice>
                            <cbc:PriceAmount currencyID="PEN">' . $comprobante["total"] . '</cbc:PriceAmount>
                            <cbc:PriceTypeCode listAgencyName="PE:SUNAT" listName="Tipo de Precio" listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo16">01</cbc:PriceTypeCode>
                        </cac:AlternativeConditionPrice>
                    </cac:PricingReference>
                    <cac:TaxTotal>
                        <cbc:TaxAmount currencyID="PEN">' . $comprobante["igv"] . '</cbc:TaxAmount>
                        <cac:TaxSubtotal>
                        <cbc:TaxableAmount currencyID="PEN">' . $comprobante["total_opgravadas"] . '</cbc:TaxableAmount>
                        <cbc:TaxAmount currencyID="PEN">' . $comprobante["igv"] . '</cbc:TaxAmount>
                        <cac:TaxCategory>
                            <cbc:Percent>18.00</cbc:Percent>
                            <cbc:TaxExemptionReasonCode listAgencyName="PE:SUNAT" listName="Afectacion del IGV" listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo07">10</cbc:TaxExemptionReasonCode>
                            <cac:TaxScheme>
                                <cbc:ID schemeAgencyName="PE:SUNAT" schemeName="Codigo de tributos" schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo05">1000</cbc:ID>
                                <cbc:Name>IGV</cbc:Name>
                                <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                            </cac:TaxScheme>
                        </cac:TaxCategory>
                        </cac:TaxSubtotal>
                    </cac:TaxTotal>
                    <cac:Item>
                        <cbc:Description>' . $venta["observacion"] . '</cbc:Description>
                    </cac:Item>
                    <cac:Price>
                        <cbc:PriceAmount currencyID="PEN">' . $comprobante["total_opgravadas"] . '</cbc:PriceAmount>
                    </cac:Price>
                </cac:DebitNoteLine>
            </DebitNote>';



            $doc->loadXML($xml);
            $doc->save($ruta . '.XML');

            //CREAR XML FIRMA

            $objfirma = new Signature();

            // $ruta_xml_firmar = $ruta . '.XML'; //es el archivo XML que se va a firmar
            $ruta = $ruta . '.XML';
            $rutacertificado = "vistas/generar_xml/";
            $flg_firma = 0; //Posicion del XML: 0 para firma
            $ruta_firma = $rutacertificado . 'certificado_prueba.pfx'; //ruta del archivo del certicado para firmar
            $pass_firma = 'ceti';
            // $actualizadoEnvio = ModeloFacturacion::mdlActualizarProcesoFacturacion(2,$tipo,$documento);
            $resp = $objfirma->signature_xml($flg_firma, $ruta, $ruta_firma, $pass_firma);

            echo '<script>

                            swal({
                                    type: "success",
                                    title: "Se Genero el XML Nota de Debito de ' . $venta["documento"] . '",
                                    showConfirmButton: true,
                                    confirmButtonText: "Cerrar"
                            }).then(function(result){
                                            if (result.value) {

                                            window.location = "procesar-ce";

                                            }
                                        })

                            </script>';
        }
    }

    /*
    * MOSTRAR MODELO DE NOTAS PARA IMPRESION
    */
    static public function ctrFEFacturaCab($tipo, $documento)
    {

        $respuesta = ModeloFacturacion::mdlFEFacturaCab($tipo, $documento);

        return $respuesta;
    }

    /*
    * MOSTRAR MODELO DE NOTAS PARA IMPRESION
    */
    static public function ctrFEFacturaDet($tipo, $documento)
    {

        $respuesta = ModeloFacturacion::mdlFEFacturaDet($tipo, $documento);

        return $respuesta;
    }

    //*GENERAR EFACT
    static public function ctrGenerarFEFacBol()
    {

        if (isset($_POST["tipo"])) {

            $datos = ModeloFacturacion::mdlFEFacturaCab($_POST["tipo"], $_POST["documento"]);
            //var_dump($datos);

            //todo: FILA 1
            $fila1 =    $datos["a1"] . ',' .
                $datos["b1"] . ',' .
                $datos["c1"] . ',' .
                $datos["d1"] . ',' .
                $datos["e1"] . ',' .
                $datos["f1"] . ',' .
                $datos["g1"] . ',,,,,,,' .
                $datos["n1"] . ',,,' .
                $datos["q1"] . ',,,,,' .
                $datos["v1"] . ',,,,,,,,,,,,,,,,' .
                $datos["al1"] . ',,,,,,' .
                $datos["ar1"] . ',,,,,,,,,,' .
                $datos["bb1"] . ',' .
                $datos["bc1"] . ',' .
                $datos["bd1"] . ',,,,' .
                $datos["bh1"] . ',';

            //todo: FILA 2
            $fila2 = ',,,,,,,,,,,,,,,,,,,,,,,,,,,,';

            //todo: FILA 3
            if (substr($datos["a3"], 0, 4) == "0003") {

                $fila3 =    $datos["a3"] . ',' .
                    $datos["b3"] . ',,,' .
                    $datos["e3"];
            } else {

                $fila3 =    ',,,,,' .
                    $datos["e3"];
            }

            $a4 = str_replace('\"', '', $datos["a4"]);
            //todo: FILA 4
            $fila4 =    $datos["a4"] . ',' .
                $datos["b4"] . ',' .
                $datos["c4"] . ',' .
                $datos["d4"] . ',' .
                $datos["e4"] . ',' .
                $datos["f4"] . ',' .
                $datos["g4"] . ',' .
                $datos["h4"] . ',' .
                $datos["i4"] . ',' .
                $datos["j4"] . ',' .
                '0002' . ',';

            //todo: FILA 5
            $fila5 =    $datos["a5"] . ',' .
                $datos["b5"] . ',' .
                $datos["c5"] . ',' .
                $datos["d5"] . ',' .
                $datos["e5"] . ',' .
                $datos["f5"] . ',' .
                $datos["g5"] . ',' .
                $datos["h5"] . ',' .
                $datos["i5"] . ',' .
                $datos["j5"] . ',' .
                $datos["k5"] . ',' .
                $datos["l5"] . ',';

            //todo: FILA 6
            require_once("/../extensiones/cantidad_en_letras_v2.php");

            if ($venta["tipo_moneda"] == "PEN") {

                $monto_letra = convertir($datos["n1"]);
            } else {

                $monto_letra = str_replace("SOLES", "DOLARES AMERICANOS", convertir($datos["n1"]));
            }

            //$monto_letras = convertir($datos["n1"]);
            $fila6 =    $monto_letra . ',,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,';

            //todo: FILA 7
            $fila7 =    $datos["a7"] . ',,,' .
                $datos["d7"] . ',' .
                $datos["e7"] . ',' .
                $datos["f7"] . ',' .
                $datos["g7"] . ',';

            $nombre = '20513613939-' . $datos["c1"] . '-' . $datos["b1"];

            $fp = fopen('../vistas/reportes_excel/csv_fe/' . $nombre . '.txt', 'w');

            fwrite($fp, $fila1 . PHP_EOL);
            fwrite($fp, $fila2 . PHP_EOL);
            fwrite($fp, $fila3 . PHP_EOL);
            fwrite($fp, $fila4 . PHP_EOL);
            fwrite($fp, $fila5 . PHP_EOL);
            fwrite($fp, $fila6 . PHP_EOL);
            fwrite($fp, $fila7 . PHP_EOL);

            $datosD = ModeloFacturacion::mdlFEFacturaDet($_POST["tipo"], $_POST["documento"]);
            //var_dump($datosD);

            foreach ($datosD as $key => $value) {

                if ($key < count($datosD) - 1) {

                    fwrite($fp, ($key + 1) . ',' .
                        $value["b9"] . ',' .
                        $value["c9"] . ',' .
                        $value["d9"] . ',' .
                        $value["e9"] . ',' .
                        $value["f9"] . ',,,' .
                        $value["i9"] . ',' .
                        $value["j9"] . ',' .
                        $value["k9"] . ',' .
                        $value["l9"] . ',' .
                        $value["m9"] . ',,,,,,' .
                        $value["s9"] . ',' .
                        $value["t9"] . ',' .
                        $value["u9"] . ',,,,,,,,,,,,,,,,' .
                        $value["ak9"] . ',' .
                        $value["al9"] . ',,,,' .
                        "\r\n");
                } else {

                    fwrite($fp, ($key + 1) . ',' .
                        $value["b9"] . ',' .
                        $value["c9"] . ',' .
                        $value["d9"] . ',' .
                        $value["e9"] . ',' .
                        $value["f9"] . ',,,' .
                        $value["i9"] . ',' .
                        $value["j9"] . ',' .
                        $value["k9"] . ',' .
                        $value["l9"] . ',' .
                        $value["m9"] . ',,,,,,' .
                        $value["s9"] . ',' .
                        $value["t9"] . ',' .
                        $value["u9"] . ',,,,,,,,,,,,,,,,' .
                        $value["ak9"] . ',' .
                        $value["al9"] . ',,,,' . PHP_EOL);
                    fwrite($fp, 'FF00FF');
                }
            }

            fclose($fp);

            $origen = 'c:/xampp2/htdocs/vascorp/vistas/reportes_excel/csv_fe/' . $nombre . '.txt';

            if ($datos["c1"] == "01") {

                //?destino prueba
                $destino = 'c:/prueba/invoice/' . $nombre . '.csv';

                //!destino produccion
                //!$destino = 'c:/daemonOSE21/documents/in/invoice/'.$nombre.'.csv';

            } else {

                //?destino prueba
                $destino = 'c:/prueba/boleta/' . $nombre . '.csv';

                //!destino produccion
                //!$destino = 'c:/daemonOSE21/documents/in/boleta/'.$nombre.'.csv';

            }

            copy($origen, $destino);
            //rename($origen, $destino);

        }

        $respuesta = "ok";
        return $respuesta;
    }

    //*GENERAR NUBE FACTURA Y BOLETA
    static public function ctrGenerarFEFacBolA()
    {

        if (isset($_POST["tipo"])) {

            $datos = ModeloFacturacion::mdlFEFacturaCabA($_POST["tipo"], $_POST["documento"]);
            //var_dump($datos);

            if ($_POST["tipo"] == "S03") {

                //todo: FILA 1
                $fila1 =    $datos["a1"] . ',' .
                    $datos["b1"] . ',' .
                    $datos["c1"] . ',' .
                    $datos["d1"] . ',' .
                    $datos["e1"] . ',' .
                    $datos["f1"] . ',' .
                    $datos["g1"] . ',,,,,,,' .
                    $datos["n1"] . ',' .
                    $datos["o1"] . ',,' .
                    $datos["q1"] . ',,,,' .
                    $datos["u1"] . ',' .
                    $datos["v1"] . ',,,,' .
                    $datos["z1"] . ',,,,,,,,,,,,' .
                    $datos["al1"] . ',,,,,,,' .
                    $datos["as1"] . ',' .
                    $datos["at1"] . ',,,,,,,,,,,,,,' .
                    $datos["bh1"] . ',' .
                    $datos["bi1"] . ',' .
                    $datos["bj1"] . ',,,,,,,,,,,,,,,,,,,,,';
            } else {

                //todo: FILA 1
                $fila1 =    $datos["a1"] . ',' .
                    $datos["b1"] . ',' .
                    $datos["c1"] . ',' .
                    $datos["d1"] . ',' .
                    $datos["e1"] . ',' .
                    $datos["f1"] . ',' .
                    $datos["g1"] . ',,,,,,,' .
                    $datos["n1"] . ',,,' .
                    $datos["q1"] . ',,,,,' .
                    $datos["v1"] . ',,,,' .
                    $datos["z1"] . ',,,,,,,,,,,,' .
                    $datos["al1"] . ',,,,,,,' .
                    $datos["as1"] . ',' .
                    $datos["at1"] . ',,,,,,,,,,,,,,' .
                    $datos["bh1"] . ',' .
                    $datos["bi1"] . ',' .
                    $datos["bj1"] . ',,,,,,,,,,,,,,,,,,,,,';
            }



            //todo: FILA 2
            $fila2 = ',,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,';

            //todo: FILA 3

            if ($_POST["tipo"] == "S03") {

                if (substr($datos["a3"], 0, 4) == "0003") {

                    $fila3 =    $datos["a3"] . ',' .
                        $datos["b3"] . ',,' .
                        $datos["e3"] . ',' .
                        $datos["f3"] . ',' .
                        $datos["g3"] . ',' .
                        $datos["h3"];
                } else {

                    $fila3 =    ',,,,' .
                        $datos["e3"] . ',' .
                        $datos["f3"] . ',' .
                        $datos["g3"] . ',' .
                        $datos["h3"];
                }
            } else {

                if (substr($datos["a3"], 0, 4) == "0003") {

                    $fila3 =    $datos["a3"] . ',' .
                        $datos["b3"] . ',,,,';
                } else {

                    $fila3 =    ',,,,,';
                }
            }



            $a4 = str_replace('\"', '', $datos["a4"]);

            //todo: FILA 4
            $fila4 =    $datos["a4"] . ',' .
                $datos["b4"] . ',' .
                $datos["c4"] . ',' .
                $datos["d4"] . ',' .
                $datos["e4"] . ',' .
                $datos["f4"] . ',' .
                $datos["g4"] . ',' .
                $datos["h4"] . ',' .
                $datos["i4"] . ',' .
                $datos["j4"] . ',' .
                $datos["k4"] . ',' .
                $datos["l4"] . ',' .
                '0002' . ',';

            //todo: FILA 5
            $fila5 =    $datos["a5"] . ',' .
                $datos["b5"] . ',' .
                $datos["c5"] . ',' .
                $datos["d5"] . ',' .
                $datos["e5"] . ',' .
                $datos["f5"] . ',' .
                $datos["g5"] . ',' .
                $datos["h5"] . ',' .
                $datos["i5"] . ',' .
                $datos["j5"] . ',' .
                $datos["k5"] . ',' .
                $datos["l5"] . ',';

            //todo: FILA 6
            require_once("../extensiones/cantidad_en_letras_v2.php");

            if ($datos["d1"] == "PEN") {

                $monto_letras = convertir($datos["n1"]);
            } else {

                $monto_letras = str_replace("SOLES", "DOLARES AMERICANOS", convertir($datos["n1"]));
            }

            #$monto_letras = convertir($datos["n1"]);
            $fila6 =    $monto_letras . ',,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,';

            //todo: FILA 7
            $fila7 =    $datos["a7"] . ',,' .
                $datos["g3"] . ',' .
                $datos["d7"] . ',' .
                $datos["e7"] . ',' .
                $datos["f7"] . ',' .
                $datos["g7"] . ',,,,,,';

            $nombre = '20513613939-' . $datos["c1"] . '-' . $datos["b1"];

            $fp = fopen('../vistas/reportes_excel/csv_fe/' . $nombre . '.txt', 'w');

            fwrite($fp, $fila1 . PHP_EOL);
            fwrite($fp, $fila2 . PHP_EOL);
            fwrite($fp, $fila3 . PHP_EOL);
            fwrite($fp, $fila4 . PHP_EOL);
            fwrite($fp, $fila5 . PHP_EOL);
            fwrite($fp, $fila6 . PHP_EOL);
            fwrite($fp, $fila7 . PHP_EOL);

            $datosD = $datos["exportacion"] == 0 ?
                ModeloFacturacion::mdlFEFacturaDetA($_POST["tipo"], $_POST["documento"]) :
                ModeloFacturacion::mdlFEFacturaDetAExportacion($_POST["tipo"], $_POST["documento"]);
            //var_dump($datosD);

            foreach ($datosD as $key => $value) {
                $fila = ($key + 1) . ',' .
                    $value["b9"] . ',' .
                    $value["c9"] . ',' .
                    $value["d9"] . ',' .
                    $value["e9"] . ',' .
                    $value["f9"] . ',,,' .
                    $value["i9"] . ',' .
                    $value["j9"] . ',' .
                    $value["k9"] . ',' .
                    $value["l9"] . ',' .
                    $value["m9"] . ',,,,,,' .
                    $value["s9"] . ',' .
                    $value["t9"] . ',' .
                    $value["u9"] . ',,,' .
                    $value["x9"] . ',,,,,,,,,,,,,' .
                    $value["ak9"] . ',' .
                    $value["al9"] . ',,,,' .
                    $value["ap9"] . ',,,,,,,,,,,,,,,,,,,,,' . PHP_EOL;

                if ($key < count($datosD) - 1) {
                    fwrite($fp, $fila);
                } else {
                    fwrite($fp, $fila . 'FF00FF');
                }
            }

            fclose($fp);

            require_once("config.php");

            $origen = ORIGEN . $nombre . '.txt';

            if ($datos["c1"] == "01") {
                $destino = DESTINO_INVOICE . $nombre . '.csv';
            } else {
                $destino = DESTINO_BOLETA . $nombre . '.csv';
            }

            // $copy = copy($origen, $destino);
            if (file_exists($origen)) {
                $rename = rename($origen, $destino);

                if ($rename) {
                    $respuesta = "okA";
                } else {
                    $respuesta = "error";
                }
            } else {
                echo 'El archivo no existe en la ruta especificada.';
            }
        }

        return $respuesta;
    }

    //* facturacion para exportacion
    static public function ctrGenerarFEFacBolB()
    {

        if (isset($_POST["tipo"])) {
            $datos = ModeloFacturacion::mdlFEFacturaCabB($_POST["documento"]);

            // FILA 1
            $fila1 = $datos["a1"] . ',' .
                $datos["b1"] . ',' .
                $datos["c1"] . ',' .
                $datos["d1"] . ',' .
                $datos["e1"] . ',' .
                $datos["f1"] . ',' .
                $datos["g1"] . self::calcularComas('g', 'n') .
                $datos["n1"] . ',' .
                $datos["o1"] . self::calcularComas('o', 'q') .
                $datos["q1"] . self::calcularComas('q', 'u') .
                $datos["u1"] . self::calcularComas('u', 'z') .
                $datos["z1"] . self::calcularComas('z', 'ap') .
                $datos["ap1"] . self::calcularComas('ap', 'as') .
                $datos["as1"] . self::calcularComas('as', 'bh') .
                $datos["bh1"] . ',' .
                $datos["bi1"] . ',' .
                $datos["bj1"] . self::calcularComas('bj', 'br'); // 'end' es un marcador para el final de tu fila, ajusta según tu necesidad.

            // FILA 2
            $fila2 = ',,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,';

            // FILA 3
            $fila3 = ',,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,';

            // FILA 4
            //$fila4 = ',,,,,,';

            // FILA 5
            $fila5 =    $datos["a5"] . ',' .
                $datos["b5"] . ',' .
                $datos["c5"] . ',' .
                $datos["d5"] . ',' .
                $datos["e5"] . ',' .
                $datos["f5"] . ',' .
                $datos["g5"] . ',' .
                $datos["h5"] . ',' .
                $datos["i5"] . ',' .
                $datos["j5"] . ',' .
                $datos["k5"] . ',' .
                $datos["l5"] . ',' .
                $datos["m5"] . ',';

            // FILA 6
            $fila6 =    $datos["a6"] . ',' .
                $datos["b6"] . ',' .
                $datos["c6"] . ',' .
                $datos["d6"] . ',' .
                $datos["e6"] . ',' .
                $datos["f6"] . ',' .
                $datos["g6"] . ',' .
                $datos["h6"] . ',' .
                $datos["i6"] . ',' .
                $datos["j6"] . ',' .
                $datos["k6"] . ',' .
                $datos["l6"] . ',';

            // FILA 7
            require_once("../extensiones/cantidad_en_letras_v2.php");
            $monto_letras = str_replace("SOLES", "DOLARES AMERICANOS", convertir($datos["n1"]));

            $fila7 =    $monto_letras . ',,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,';

            // FILA 8
            $fila8 =    $datos["a8"] . ',,' .
                $datos["g3"] . ',' .
                $datos["d8"] . ',' .
                $datos["e8"] . ',' .
                $datos["f8"] . ',' .
                $datos["g8"] . ',,,,,,';

            $nombre = '20513613939-' . $datos["c1"] . '-' . $datos["b1"];

            $fp = fopen('../vistas/reportes_excel/csv_fe/' . $nombre . '.txt', 'w');
            fwrite($fp, $fila1 . PHP_EOL);
            fwrite($fp, $fila2 . PHP_EOL);
            fwrite($fp, $fila3 . PHP_EOL);
            //fwrite($fp, $fila4 . PHP_EOL);
            fwrite($fp, $fila5 . PHP_EOL);
            fwrite($fp, $fila6 . PHP_EOL);
            fwrite($fp, $fila7 . PHP_EOL);
            fwrite($fp, $fila8 . PHP_EOL);

            // FILA 9
            $datosD = ModeloFacturacion::mdlFEFacturaDetAExportacion($_POST["documento"]);
            require_once("config.php");

            foreach ($datosD as $key => $value) {


                // Verificar si el tipo de producto existe en el arreglo $codigo_arancel
                if (array_key_exists($value["linea"], $codigo_arancel)) {
                    // Obtener el código arancelario basado en el tipo de producto
                    $codigo = $codigo_arancel[$value["linea"]];
                    // $codigo = "";
                } else {
                    // Manejar el caso en que el tipo de producto no se encuentre en el arreglo
                    $codigo = "No encontrado"; // O cualquier otro manejo de error que prefieras
                }

                $fila = ($key + 1) . ',' .
                    $value["b9"] . ',' .
                    $value["c9"] . ',' .
                    $value["d9"] . ',' .
                    $value["e9"] . ',' .
                    $value["f9"] . self::calcularComas('f', 'i') .
                    $value["i9"] . ',' .
                    $value["j9"] . ',' .
                    $value["k9"] . ',' .
                    $value["l9"] . ',' .
                    $value["m9"] . self::calcularComas('m', 'r') .
                    $codigo . ',' .
                    $value["s9"] . ',' .
                    $value["t9"] . ',' .
                    $value["u9"] . self::calcularComas('u', 'w') .
                    $value["w9"] . ',' .
                    $value["x9"] . self::calcularComas('x', 'ak') .
                    $value["ak9"] . ',' .
                    $value["al9"] . self::calcularComas('al', 'ap') .
                    $value["ap9"] . self::calcularComas('ap', 'ar') . PHP_EOL;

                if ($key < count($datosD) - 1) {
                    fwrite($fp, $fila);
                } else {
                    fwrite($fp, $fila . 'FF00FF');
                }
            }

            fclose($fp);

            $origen = ORIGEN . $nombre . '.txt';

            if ($datos["c1"] == "01") {
                $destino = DESTINO_INVOICE . $nombre . '.csv';
            } else {
                $destino = DESTINO_BOLETA . $nombre . '.csv';
            }

            // $copy = copy($origen, $destino);
            if (file_exists($origen)) {
                $rename = rename($origen, $destino);

                if ($rename) {
                    $respuesta = "okA";
                } else {
                    $respuesta = "error";
                }
            } else {
                echo 'El archivo no existe en la ruta especificada.';
            }

            return $respuesta;
        }
    }

    static public function letraAIndice($letra)
    {
        $letra = strtolower($letra);
        $indice = 0;
        $longitud = strlen($letra);
        for ($i = 0; $i < $longitud; $i++) {
            $indice *= 26;
            $indice += ord($letra[$i]) - ord('a') + 1;
        }
        return $indice;
    }

    static public function calcularComas($letraInicio, $letraFin)
    {
        $inicio = self::letraAIndice($letraInicio);
        $fin = self::letraAIndice($letraFin);

        // La lógica para determinar el número de comas se mantiene,
        // pero ahora se aplica a los índices numéricos.
        $numComas = max(0, $fin - $inicio);
        return str_repeat(',', $numComas);
    }

    //*GENERAR NUBE NOTA CREDITO
    static public function ctrGenerarFENCA()
    {

        if (isset($_POST["tipo"])) {

            $datos = ModeloFacturacion::mdlFENCACabA($_POST["tipo"], $_POST["documento"]);
            //var_dump($datos);

            //todo: FILA 1
            $fila1 =    $datos["a1"] . ',' .
                $datos["b1"] . ',' .
                $datos["c1"] . ',' .
                $datos["d1"] . ',' .
                $datos["e1"] . ',' .
                $datos["f1"] . ',,,,,,,' .
                $datos["m1"] . ',,,' .
                $datos["p1"] . ',,,,' .
                $datos["t1"] . ',,,,,,,,,,,' .
                $datos["ae1"] . ',,,,,' .
                $datos["aj1"] . ',' .
                $datos["ak1"] . ',,,' .
                $datos["an1"] . ',';

            //todo: FILA 2
            $fila2 = ',,,,,,,,,';

            //todo: FILA 3
            $fila3 =    $datos["a3"] . ',' .
                $datos["b3"] . ',' .
                $datos["c3"] . ',' .
                $datos["d3"] . ',' .
                $datos["e3"] . ',' .
                $datos["f3"] . ',' .
                $datos["g3"] . ',' .
                $datos["h3"] . ',' .
                $datos["i3"] . ',' .
                $datos["j3"] . ',' .
                $datos["k3"] . ',' .
                $datos["l3"] . ',' .
                '0000' . ',';

            //todo: FILA 4
            $fila4 =    $datos["a4"] . ',' .
                $datos["b4"] . ',' .
                $datos["c4"] . ',' .
                $datos["d4"] . ',' .
                $datos["e4"] . ',' .
                $datos["f4"] . ',' .
                $datos["g4"] . ',' .
                $datos["h4"] . ',' .
                $datos["i4"] . ',' .
                $datos["j4"] . ',' .
                $datos["k4"] . ',' .
                $datos["l4"] . ',';

            //todo: FILA 5
            require_once("../extensiones/cantidad_en_letras_v2.php");
            $monto_letras = convertir($datos["m1"]);
            $fila5 =    $monto_letras . ',,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,';

            //todo: FILA 6
            $fila6 =    $datos["a6"] . ',,,' .
                $datos["d6"] . ',' .
                $datos["e6"] . ',' .
                $datos["f6"] . ',' .
                $datos["g6"] . ',,,,,,,,,,,,,,,,,,,,,,,,,,,,,';

            //todo: FILA 7
            $fila7 =    $datos["a7"] . ',' .
                $datos["b7"] . ',' .
                $datos["c7"] . ',' .
                $datos["d7"] . ',' .
                $datos["e7"] . ',' .
                $datos["f7"] . ',';

            $nombre = '20513613939-07' . '-' . $datos["b1"];

            $fp = fopen('../vistas/reportes_excel/csv_fe/' . $nombre . '.txt', 'w');

            fwrite($fp, $fila1 . PHP_EOL);
            fwrite($fp, $fila2 . PHP_EOL);
            fwrite($fp, $fila3 . PHP_EOL);
            fwrite($fp, $fila4 . PHP_EOL);
            fwrite($fp, $fila5 . PHP_EOL);
            fwrite($fp, $fila6 . PHP_EOL);
            fwrite($fp, $fila7 . PHP_EOL);

            if (
                substr($_POST["documento"], 0, 4) == "B001" ||
                substr($_POST["documento"], 0, 4) == "F001" ||
                substr($_POST["documento"], 0, 4) == "B003" ||
                substr($_POST["documento"], 0, 4) == "F003" ||
                substr($_POST["documento"], 0, 4) == "FR01" ||
                substr($_POST["documento"], 0, 4) == "BR01"
            ) {

                //*Uniaades

                $datosD = ModeloFacturacion::mdlFENCDetA($_POST["tipo"], $_POST["documento"]);
                //var_dump($datosD);

                foreach ($datosD as $key => $value) {

                    if ($key < count($datosD) - 1) {

                        fwrite($fp, ($key + 1) . ',' .
                            trim($value["b9"]) . ',' .
                            trim($value["c9"]) . ',' .
                            trim($value["d9"]) . ',' .
                            trim($value["e9"]) . ',' .
                            trim($value["f9"]) . ',,,' .
                            trim($value["i9"]) . ',' .
                            trim($value["j9"]) . ',' .
                            trim($value["k9"]) . ',' .
                            trim($value["l9"]) . ',' .
                            trim($value["m9"]) . ',,,,,,' .
                            trim($value["s9"]) . ',' .
                            trim($value["t9"]) . ',' .
                            trim($value["u9"]) . ',,,' .
                            trim($value["x9"]) . ',,,,,,' .
                            trim($value["ad9"]) . ',,,,,,' .
                            "\r\n");
                    } else {

                        fwrite($fp, ($key + 1) . ',' .
                            trim($value["b9"]) . ',' .
                            trim($value["c9"]) . ',' .
                            trim($value["d9"]) . ',' .
                            trim($value["e9"]) . ',' .
                            trim($value["f9"]) . ',,,' .
                            trim($value["i9"]) . ',' .
                            trim($value["j9"]) . ',' .
                            trim($value["k9"]) . ',' .
                            trim($value["l9"]) . ',' .
                            trim($value["m9"]) . ',,,,,,' .
                            trim($value["s9"]) . ',' .
                            trim($value["t9"]) . ',' .
                            trim($value["u9"]) . ',,,' .
                            trim($value["x9"]) . ',,,,,,' .
                            trim($value["ad9"]) . ',,,,,,' . PHP_EOL);
                        fwrite($fp, 'FF00FF');
                    }
                }
            } else {

                //*CONCEPTO
                $datosD = ModeloFacturacion::mdlFENCDetB($_POST["tipo"], $_POST["documento"]);
                #var_dump($datosD["d8"]);   

                $datosD["d8"] = str_replace(",", "A", $datosD["d8"]);

                $fila8 =    '1' . ',' .
                    $datosD["b8"] . ',' .
                    $datosD["c8"] . ',' .
                    $datosD["d8"] . ',' .
                    $datosD["e8"] . ',' .
                    $datosD["f8"] . ',,,' .
                    $datosD["i8"] . ',' .
                    $datosD["j8"] . ',' .
                    $datosD["k8"] . ',' .
                    $datosD["l8"] . ',' .
                    $datosD["m8"] . ',,,,,,,' .
                    $datosD["t8"] . ',' .
                    $datosD["u8"] . ',,,' .
                    $datosD["x8"] . ',,,,,,' .
                    $datosD["ad8"] . ',,,,,,';

                fwrite($fp, $fila8 . PHP_EOL);
                fwrite($fp, 'FF00FF');
            }

            fclose($fp);

            require_once("config.php");

            $origen = ORIGEN . $nombre . '.txt';

            $destino = DESTINO_NOTA_CREDITO . $nombre . '.csv';

            // $copy = copy($origen, $destino);
            if (file_exists($origen)) {
                $rename = rename($origen, $destino);

                if ($rename) {
                    $respuesta = "okA";
                } else {
                    $respuesta = "error";
                }
            } else {
                echo 'El archivo no existe en la ruta especificada.';
            }
        }

        return $respuesta;
    }

    //*GENERAR NUBE DEBITO
    static public function ctrGenerarFENDA()
    {

        if (isset($_POST["tipo"])) {

            $datos = ModeloFacturacion::mdlFENDACabA($_POST["tipo"], $_POST["documento"]);
            //var_dump($datos);

            //todo: FILA 1
            $fila1 =    $datos["a1"] . ',' .
                $datos["b1"] . ',' .
                $datos["c1"] . ',' .
                $datos["d1"] . ',' .
                $datos["e1"] . ',' .
                $datos["f1"] . ',,,,,,,' .
                $datos["m1"] . ',,,' .
                $datos["p1"] . ',,,,' .
                $datos["t1"] . ',,,,,,,,,,,' .
                $datos["ae1"] . ',,,,,' .
                $datos["aj1"] . ',' .
                $datos["ak1"] . ',,,' .
                $datos["an1"] . ',';

            //todo: FILA 2
            $fila2 = ',,,,,,,,,';

            //todo: FILA 3
            $fila3 =    $datos["a3"] . ',' .
                $datos["b3"] . ',' .
                $datos["c3"] . ',' .
                $datos["d3"] . ',' .
                $datos["e3"] . ',' .
                $datos["f3"] . ',' .
                $datos["g3"] . ',' .
                $datos["h3"] . ',' .
                $datos["i3"] . ',' .
                $datos["j3"] . ',' .
                $datos["k3"] . ',' .
                $datos["l3"] . ',' .
                '0000' . ',';

            //todo: FILA 4
            $fila4 =    $datos["a4"] . ',' .
                $datos["b4"] . ',' .
                $datos["c4"] . ',' .
                $datos["d4"] . ',' .
                $datos["e4"] . ',' .
                $datos["f4"] . ',' .
                $datos["g4"] . ',' .
                $datos["h4"] . ',' .
                $datos["i4"] . ',' .
                $datos["j4"] . ',' .
                $datos["k4"] . ',' .
                $datos["l4"] . ',';

            //todo: FILA 5
            require_once("../extensiones/cantidad_en_letras_v2.php");
            $monto_letras = convertir($datos["m1"]);
            $fila5 =    $monto_letras . ',,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,';

            //todo: FILA 6
            $fila6 =    $datos["a6"] . ',,,' .
                $datos["d6"] . ',' .
                $datos["e6"] . ',' .
                $datos["f6"] . ',' .
                $datos["g6"] . ',,,,,,,,,,,,,,,,,,,,,,,,,,,,,';

            //todo: FILA 7
            $fila7 =    $datos["a7"] . ',' .
                $datos["b7"] . ',' .
                $datos["c7"] . ',' .
                $datos["d7"] . ',' .
                $datos["e7"] . ',' .
                $datos["f7"] . ',';

            $nombre = '20513613939-08' . '-' . $datos["b1"];

            $fp = fopen('../vistas/reportes_excel/csv_fe/' . $nombre . '.txt', 'w');

            fwrite($fp, $fila1 . PHP_EOL);
            fwrite($fp, $fila2 . PHP_EOL);
            fwrite($fp, $fila3 . PHP_EOL);
            fwrite($fp, $fila4 . PHP_EOL);
            fwrite($fp, $fila5 . PHP_EOL);
            fwrite($fp, $fila6 . PHP_EOL);
            fwrite($fp, $fila7 . PHP_EOL);

            $datosD = ModeloFacturacion::mdlFENDDetA($_POST["tipo"], $_POST["documento"]);
            //var_dump($datosD);

            foreach ($datosD as $key => $value) {

                if ($key < count($datosD) - 1) {

                    fwrite($fp, ($key + 1) . ',' .
                        $value["b8"] . ',' .
                        $value["c8"] . ',' .
                        $value["d8"] . ',' .
                        $value["e8"] . ',' .
                        $value["f8"] . ',,,' .
                        $value["i8"] . ',' .
                        $value["j8"] . ',' .
                        $value["k8"] . ',' .
                        $value["l8"] . ',' .
                        $value["m8"] . ',,,,,,,' .
                        $value["t8"] . ',' .
                        $value["u8"] . ',,,' .
                        $value["x8"] . ',,,,,,' .
                        $value["ad8"] . ',,,,,,' .
                        "\r\n");
                } else {

                    fwrite($fp, ($key + 1) . ',' .
                        $value["b8"] . ',' .
                        $value["c8"] . ',' .
                        $value["d8"] . ',' .
                        $value["e8"] . ',' .
                        $value["f8"] . ',,,' .
                        $value["i8"] . ',' .
                        $value["j8"] . ',' .
                        $value["k8"] . ',' .
                        $value["l8"] . ',' .
                        $value["m8"] . ',,,,,,,' .
                        $value["t8"] . ',' .
                        $value["u8"] . ',,,' .
                        $value["x8"] . ',,,,,,' .
                        $value["ad8"] . ',,,,,,' . PHP_EOL);
                    fwrite($fp, 'FF00FF');
                }
            }

            fclose($fp);

            require_once("config.php");

            $origen = ORIGEN . $nombre . '.txt';

            $destino = DESTINO_NOTA_DEBITO . $nombre . '.csv';

            // $copy = copy($origen, $destino);
            if (file_exists($origen)) {
                $rename = rename($origen, $destino);

                if ($rename) {
                    $respuesta = "okA";
                } else {
                    $respuesta = "error";
                }
            } else {
                echo 'El archivo no existe en la ruta especificada.';
            }
        }

        return $respuesta;
    }

    //*GENERAR NUBE GRR
    static public function ctrGenerarGuia()
    {
        if (isset($_POST["tipo"])) {
            $datos = ModeloFacturacion::mdlFEGuia($_POST["tipo"], $_POST["documento"]);

            //todo: FILA 1
            $fila1 =    $datos["a1"] . ',' .
                $datos["b1"] . ',' .
                $datos["c1"] . ',' .
                $datos["d1"] . ',' .
                $datos["e1"] . ',' .
                $datos["f1"] . ',' .
                $datos["g1"] . ',' .
                $datos["h1"] . ',' .
                $datos["i1"] . ',' .
                $datos["j1"] . ',';

            //todo: FILA 2
            $fila2 = ',,,,,,,,,,,,,,';

            //todo: FILA 3
            $fila3 =    $datos["a3"] . ',' .
                $datos["b3"] . ',' .
                $datos["c3"] . ',' .
                $datos["d3"] . ',' .
                $datos["e3"] . ',';

            //todo: FILA 4
            $fila4 =    $datos["a4"] . ',' .
                $datos["b4"] . ',' .
                $datos["c4"] . ',' .
                $datos["d4"] . ',' .
                $datos["e4"] . ',' .
                $datos["f4"] . ',';

            //todo: FILA 5
            $fila5 =    $datos["a5"] . ',' .
                $datos["b5"] . ',' .
                $datos["c5"] . ',' .
                $datos["d5"] . ',' .
                $datos["e5"] . ',';

            //todo: FILA 6
            $fila6 = ',,,,';

            //todo: FILA 7
            $fila7 =    $datos["a7"] . ',' .
                $datos["b7"] . ',' .
                $datos["c7"] . ',' .
                $datos["d7"] . ',' .
                $datos["e7"] . ',' .
                $datos["f7"] . ',' .
                $datos["g7"] . ',' .
                $datos["h7"] . ',' .
                $datos["i7"] . ',' .
                $datos["j7"] . ',';

            //todo: FILA 8
            $fila8 =    $datos["a8"] . ',' .
                $datos["b8"] . ',' .
                $datos["c8"] . ',' .
                $datos["d8"] . ',' .
                $datos["e8"] . ',' .
                $datos["f8"] . ',' .
                $datos["g8"] . ',' .
                $datos["h8"] . ',' .
                $datos["i8"] . ',' .
                $datos["j8"] . ',' .
                $datos["k8"] . ',';

            //todo: FILA 9
            $fila9 = ',,,,,,,,,,,,,,,,,,,,';

            //todo: FILA 10
            //r10 debe reemplazar las "," por espacios en blanco
            $datos["r10"] = str_replace(",", "", $datos["r10"]);

            $fila10 =    $datos["a10"] . ',' .
                $datos["b10"] . ',' .
                $datos["c10"] . ',' .
                $datos["d10"] . ',' .
                $datos["e10"] . ',' .
                $datos["f10"] . ',' .
                $datos["g10"] . ',' .
                $datos["h10"] . ',' .
                $datos["i10"] . ',' .
                $datos["j10"] . ',' .
                $datos["k10"] . ',' .
                $datos["l10"] . ',' .
                $datos["m10"] . ',' .
                $datos["n10"] . ',' .
                $datos["o10"] . ',' .
                $datos["p10"] . ',' .
                $datos["q10"] . ',' .
                $datos["r10"] . ',' .
                $datos["s10"] . ',' .
                $datos["t10"] . ',' .
                $datos["u10"] . ',' .
                $datos["v10"] . ',' .
                $datos["w10"] . ',,,,' .
                $datos["aa10"] . ',,,,,,,,,,,,,' .
                $datos["ao10"] . ',';

            //todo: FILA 11
            $fila11 =    $datos["a11"] . ',' .
                $datos["b11"] . ',' .
                $datos["c11"] . ',' .
                $datos["d11"] . ',' .
                $datos["e11"] . ',';

            $nombre = '20513613939-' . $datos["c1"] . '-' . $datos["b1"];

            $fp = fopen('../vistas/reportes_excel/csv_fe/' . $nombre . '.txt', 'w');

            fwrite($fp, $fila1 . PHP_EOL);
            fwrite($fp, $fila2 . PHP_EOL);
            fwrite($fp, $fila3 . PHP_EOL);
            fwrite($fp, $fila4 . PHP_EOL);
            fwrite($fp, $fila5 . PHP_EOL);
            fwrite($fp, $fila6 . PHP_EOL);
            fwrite($fp, $fila7 . PHP_EOL);
            fwrite($fp, $fila8 . PHP_EOL);
            fwrite($fp, $fila9 . PHP_EOL);
            fwrite($fp, $fila10 . PHP_EOL);
            fwrite($fp, $fila11 . PHP_EOL);

            $datosD = ModeloFacturacion::mdlFEGuiaDetA($_POST["tipo"], $_POST["documento"]);
            //var_dump($datosD);

            foreach ($datosD as $key => $value) {

                if ($key < count($datosD) - 1) {

                    fwrite($fp, ($key + 1) . ',' .
                        $value["b12"] . ',' .
                        $value["c12"] . ',' .
                        $value["d12"] . ',' .
                        $value["e12"] . ',' .
                        $value["f12"] . ',' .
                        "\r\n");
                } else {

                    fwrite($fp, ($key + 1) . ',' .
                        $value["b12"] . ',' .
                        $value["c12"] . ',' .
                        $value["d12"] . ',' .
                        $value["e12"] . ',' .
                        $value["f12"] . ',' . PHP_EOL);
                    fwrite($fp, 'FF00FF');
                }
            }

            fclose($fp);

            require_once("config.php");

            $origen = ORIGEN . $nombre . '.txt';

            $destino = DESTINO_GUIA_REMISION . $nombre . '.csv';

            // $copy = copy($origen, $destino);
            if (file_exists($origen)) {
                $rename = rename($origen, $destino);

                if ($rename) {
                    $respuesta = "okA";
                } else {
                    $respuesta = "error";
                }
            } else {
                echo 'El archivo no existe en la ruta especificada.';
            }
        }

        return $respuesta;
    }

    static public function ctrAnularDocumento()
    {

        if (isset($_GET["documento"])) {

            $documento = $_GET["documento"];
            $tipo = $_GET["tipo"];
            #var_dump($documento,$tipo);

            #regresar stock al almacén
            $articulo = ModeloFacturacion::mdlRegresarStock($tipo, $documento);
            #var_dump($articulo);     

            #eliminar movimientos detalle
            $detalle = ModeloFacturacion::mdlEliminarDetalle($tipo, $documento);
            #var_dump($detalle);   

            $usureg = $_SESSION["nombre"];
            $pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);
            $motivo = isset($_GET["motivo"]) ? trim($_GET["motivo"]) : null;
            if ($tipo !== "S70") {
                $motivo = null;
            }

            #anular cabecera
            $cabecera = ModeloFacturacion::mdlAnularCabecera($tipo, $documento, $_SESSION["id"], $usureg, $pcreg, $motivo);
            #var_dump($cabecera); 

            #eliminar cta cte
            if ($tipo == "S03") {

                $tip = "01";
                $pagina = "facturas";
            } else if ($tipo == "S02") {

                $tip = "03";
                $pagina = "boletas";
            } else if ($tipo == "E05") {

                $tip = "07";
                $pagina = "ver-nota-credito";
            } else if ($tipo = "S70") {

                $tip = "09";
                $pagina = "proformas";
            } else if ($tipo = "S01") {

                $tip = "AA";
                $pagina = "guias-remision";
            }

            $cta = ModeloFacturacion::mdlEliminarCta($tip, $documento);
            #var_dump($cta); 

            if ($cabecera == "ok") {

                echo '<script>

                swal({
                    type: "success",
                    title: "El documento ha sido anulada correctamente",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar",
                    closeOnConfirm: false
                    }).then(function(result){
                        if (result.value) {

                        window.location = "' . $pagina . '";

                        }
                    })
    
                </script>';
            }
        }
    }

    static public function ctrEliminarDocumento()
    {

        if (isset($_GET["documentoE"])) {

            $documento = $_GET["documentoE"];
            $tipo = $_GET["tipo"];
            $pagina = $_GET["pagina"];
            var_dump($documento, $tipo, $pagina);

            $respuesta = ModeloFacturacion::mdlEliminarDocumento($tipo, $documento);
            var_dump($respuesta);

            if ($respuesta == "ok") {

                echo '<script>

                swal({
                    type: "success",
                    title: "El documento ha sido anulada correctamente",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar",
                    closeOnConfirm: false
                    }).then(function(result){
                        if (result.value) {

                        window.location = "' . $pagina . '";

                        }
                    })
    
                </script>';
            }
        }
    }

    static public function ctrCargarImagen()
    {

        if (isset($_POST["documento"])) {

            //var_dump($_POST["documento"]);

            //*CARGO
            $rutaCar = $_POST["imagenActualCar"];

            if (
                isset($_FILES["editarCargo"]["tmp_name"]) &&
                !empty($_FILES["editarCargo"]["tmp_name"])
            ) {

                list($ancho, $alto) = getimagesize($_FILES["editarCargo"]["tmp_name"]);

                $nuevoAncho = $ancho * 1;
                $nuevoAlto = $alto * 1;

                if (!empty($_POST["imagenActualCar"]) && $_POST["imagenActualCar"] != $_FILES["editarCargo"]["tmp_name"]) {

                    unlink($_POST["imagenActualCar"]);
                    clearstatcache();
                }

                if ($_FILES["editarCargo"]["type"] == "image/jpeg") {

                    /*=============================================
                    GUARDAMOS LA IMAGEN EN EL DIRECTORIO
                    =============================================*/

                    $rutaCarB = "../imagenes_vasco/" . $_POST["tipo"] . "/cargos/C" . $_POST["tipo"] . $_POST["documento"] . ".jpg";

                    $origen = imagecreatefromjpeg($_FILES["editarCargo"]["tmp_name"]);

                    $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

                    imagecopyresized($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

                    imagejpeg($destino, $rutaCarB);
                }

                if ($_FILES["editarCargo"]["type"] == "image/png") {

                    /*=============================================
                    GUARDAMOS LA IMAGEN EN EL DIRECTORIO
                    =============================================*/

                    $rutaCarB = "../imagenes_vasco/" . $_POST["tipo"] . "/cargos/C" . $_POST["tipo"] . $_POST["documento"] . ".png";

                    $origen = imagecreatefromjpeg($_FILES["editarCargo"]["tmp_name"]);

                    $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

                    imagecopyresized($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

                    imagejpeg($destino, $rutaCarB);
                }
            }

            //*RECEPCION
            $rutaRep = $_POST["imagenActualRep"];

            if (
                isset($_FILES["editarRecepcion"]["tmp_name"]) &&
                !empty($_FILES["editarRecepcion"]["tmp_name"])
            ) {

                list($ancho, $alto) = getimagesize($_FILES["editarRecepcion"]["tmp_name"]);

                $nuevoAncho = $ancho * 1;
                $nuevoAlto = $alto * 1;

                if (!empty($_POST["imagenActualRep"]) && $_POST["imagenActualRep"] != $_FILES["editarRecepcion"]["tmp_name"]) {

                    unlink($_POST["imagenActualRep"]);
                    clearstatcache();
                }

                if ($_FILES["editarRecepcion"]["type"] == "image/jpeg") {

                    /*=============================================
                    GUARDAMOS LA IMAGEN EN EL DIRECTORIO
                    =============================================*/

                    $rutaRepB = "../imagenes_vasco/" . $_POST["tipo"] . "/recepcion/R" . $_POST["tipo"] . $_POST["documento"] . ".jpg";

                    $origen = imagecreatefromjpeg($_FILES["editarRecepcion"]["tmp_name"]);

                    $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

                    imagecopyresized($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

                    imagejpeg($destino, $rutaRepB);
                }

                if ($_FILES["editarRecepcion"]["type"] == "image/png") {

                    /*=============================================
                    GUARDAMOS LA IMAGEN EN EL DIRECTORIO
                    =============================================*/

                    $rutaRepB = "../imagenes_vasco/" . $_POST["tipo"] . "/recepcion/R" . $_POST["tipo"] . $_POST["documento"] . ".png";

                    $origen = imagecreatefromjpeg($_FILES["editarRecepcion"]["tmp_name"]);

                    $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

                    imagecopyresized($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

                    imagejpeg($destino, $rutaRepB);
                }
            }

            if (isset($rutaCarB)) {

                $rutaCargo = $rutaCarB;
            } else {

                $rutaCargo = $rutaCar;
            }

            if (isset($rutaRepB)) {

                $rutaRecepcion = $rutaRepB;
            } else {

                $rutaRecepcion = $rutaRep;
            }

            $datos = array(
                "tipo"      => $_POST["tipo"],
                "documento" => $_POST["documento"],
                "cargo"     => $rutaCargo,
                "recepcion" => $rutaRecepcion
            );
            //var_dump($rutaCar, $rutaRep);
            //var_dump($rutaCarB, $rutaRepB);
            #var_dump($rutaCargo, $rutaRecepcion);
            $respuesta = ModeloFacturacion::mdlActualizarCarRep($datos);

            if ($_POST["tipo"] == "S03") {

                $salto = "facturas";
            } else if ($_POST["tipo"] == "S02") {

                $salto = "boletas";
            } else if ($_POST["tipo"] == "S70") {

                $salto = "proformas";
            }


            echo '<script>

                swal({
                    type: "success",
                    title: "Se han cargado las imágenes",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                    }).then(function(result){
                                if (result.value) {

                                window.location = "' . $salto . '";

                                }
                            })

            </script>';
        }
    }

    static public function iniciales($nombre, $dni)
    {

        $nombre = explode(" ", $nombre);

        if (substr($dni, 0, 2) == "10") {

            $resNombre = $nombre[0] . ' ' . $nombre[2];
        } else {


            $resNombre = $nombre[0] . ' ' . $nombre[1];
        }

        /* if(isset($nombre[2])){

            $resNombre = $nombre[0].' '.$nombre[1];

        }else{

            $resNombre = $nombre[0];

        } */

        #$resNombre = $nombre[0].' '.$nombre[2];

        return $resNombre;
    }

    static public function ctrAsignarCuenta()
    {

        if (isset($_POST["nroDocCta"])) {

            if ($_POST["tipDocCta"] == "FACTURA") {

                $tipo = "S03";
                $rura = "facturas";
            } else if ($_POST["tipDocCta"] == "BOLETA") {

                $tipo = "S02";
                $ruta = "boletas";
            } else if ($_POST["tipDocCta"] == "NC") {

                $tipo = "E05";
                $ruta = "ver-nota-credito";
            } else if ($_POST["tipDocCta"] == "ND") {

                $tipo = "S05";
                $ruta = "ver-nota-credito";
            }

            $datos =  array(

                "tipo" => $tipo,
                "documento" => $_POST["nroDocCta"],
                "cuenta" => $_POST["formaPagoCta"]

            );

            #var_dump($datos);

            $respuesta = ModeloFacturacion::mdlActualizarCuenta($datos);
            #var_dump($respuesta);

            if ($respuesta == "ok") {

                echo '<script>

                swal({
                      type: "success",
                      title: "Se guardo correctamente",
                      showConfirmButton: true,
                      confirmButtonText: "Cerrar"
                      }).then(function(result){
                                if (result.value) {

                                window.location = "' . $ruta . '";

                                }
                            })

                </script>';
            } else {

                echo '<script>

                    swal({
                        type: "error",
                        title: "No se pudo guardar!",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar"
                        }).then(function(result){
                            if (result.value) {

                            window.location = "' . $ruta . '";

                            }
                        })

                </script>';
            }
        }
    }

    static public function ctrCancelarCuenta3()
    {

        if (isset($_POST["cancelarDocumento2"])) {

            $tabla = "cuadrar_caja";
            $usureg = $_SESSION["nombre"];
            $pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);

            $datos = array(
                "id"            => $_POST["idCuenta3"],
                "tipo_doc"      => $_POST["cancelarTipoDocumento2"],
                "num_cta"       => $_POST["cancelarDocumentoOriginal2"],
                "cliente"       => $_POST["cancelarCliente2"],
                "vendedor"      => $_POST["cancelarVendedor2"],
                "monto"         => $_POST["cancelarMonto3"],
                "notas"         => $_POST["cancelarNota2"],
                "usuario"       => $_POST["cancelarUsuario2"],
                "fecha"         => $_POST["cancelarFechaUltima2"],
                "fecha_ven"     => $_POST["cancelarVencimientoOrigen2"],
                "cod_pago"      => $_POST["cancelarCodigo2"],
                "doc_origen"    => $_POST["cancelarDocumento2"],
                "saldo"         => 0,
                "tip_mov"       => "-",
                "usureg"        => $usureg,
                "pcreg"         => $pcreg,
                "fecha_ori"     => $_POST["cancelarFechaOrigen2"]
            );

            $cuenta = ControladorCuentas::ctrMostrarCuentas("id", $_POST["idCuenta3"]);

            $respuesta = ModeloFacturacion::mdlIngresarCuenta($tabla, $datos);

            if ($respuesta == "ok") {

                echo '<script>	

            		swal({
            			  type: "success",
            			  title: "Se registro el pado correctamente",
            			  showConfirmButton: true,
            			  confirmButtonText: "Cerrar"
            			  }).then(function(result){
            						if (result.value) {

            						window.location = "cuadre-caja";

            						}
            					})

            		</script>';
            }
        }
    }

    //**NUEVA VERSION DE FATURACION */
    static public function ctrFacturarN()
    {
        if (isset($_POST["codPedido"])) {

            // Datos
            $codigo = $_POST["codPedido"];
            $tipoDocumento = $_POST["tdoc"];
            $almacen = $_SESSION["almacen"] == "01" ? "stock01" : "stock05";
            $codigoAlmacen = $_SESSION["almacen"] == "01" ? "01" : "05";
            $documento = str_replace('-', '', $_POST["serie"]);
            $cliente = $_POST["codCli"];
            $vendedor = $_POST["codVen"];
            $dscto = $_POST["dscto"] == "" ? 0 : $_POST["dscto"];

            $usuario = $_POST["idUsuario"];
            $docOrigen = $_POST["codPedido"];

            $docDestino = !empty($_POST['serieSeparado']) ? $_POST['serieSeparado'] : null;
            $docDest = $docDestino ? str_replace('-', '', $docDestino) : '';
            $checkBoleta = !empty($_POST['chkBoleta']) ? $_POST['chkBoleta'] : null;
            $checkFactura = !empty($_POST['chkFactura']) ? $_POST['chkFactura'] : null;

            $exportacion = substr($documento, 0, 2) == "FX" ? 1 : 0;
            $tipo_moneda = substr($documento, 0, 2) == "FX" ? 2 : 1;

            if ($checkBoleta == "on" && $checkFactura == null) {
                $tipoDestino = "S02";
                $nombresDestino = "BOLETA";
                $tipoCuenta = "03";
            } else {
                $tipoDestino = "S03";
                $nombresDestino = "FACTURA";
                $tipoCuenta = "01";
            }

            $chofer = !empty($_POST['chofer']) ? $_POST['chofer'] : null;
            $movilidad = !empty($_POST['carro']) ? $_POST['carro'] : null;
            $peso = !empty($_POST['peso']) ? $_POST['peso'] : null;
            $bultos = !empty($_POST['bultos']) ? $_POST['bultos'] : null;

            $tipNota = !empty($_POST['tdocorigen']) ? $_POST['tdocorigen'] : null;
            $origenVenta = !empty($_POST['serieOrigen']) ? $_POST['serieOrigen'] : null;
            $fechaOrigen = !empty($_POST['fechaOrigen']) ? $_POST['fechaOrigen'] : null;
            $notaMotivo = !empty($_POST['notaMotivo']) ? $_POST['notaMotivo'] : null;

            $usureg = $_SESSION["nombre"];
            $pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);

            // Determinar tipo y nombreTipo según tipoDocumento
            switch ($tipoDocumento) {
                case "00":
                    $tipo = "S01";
                    $nombreTipo = "GUIA REMISION";
                    $stock = ModeloArticulos::mdlActualizarStockPedido($codigo, $almacen);
                    $serie = (substr($documento, 0, 1) == "0") ? substr($documento, 0, 3) : substr($documento, 0, 4);
                    ModeloFacturacion::mdlActualizarTalonarioGuia($serie);
                    if ($checkBoleta == "on" || $checkFactura == "on") {

                        $movimientosDestino = self::ctrRegistrarMovimientos($codigo, $tipoDestino, $docDest, $cliente, $vendedor, $dscto, $nombresDestino, $codigoAlmacen);
                        $ventasDestino = self::ctrRegistrarVentas($codigo, $tipoDestino, $docDest, "", $documento, $usuario, $nombresDestino, $usureg, $pcreg, $chofer, $movilidad, $peso, $bultos, $exportacion, $tipo_moneda);

                        $serieDestino = (substr($docDest, 0, 1) == "0") ? substr($docDest, 0, 3) : substr($docDest, 0, 4);
                        if ($checkBoleta == "on" && $checkFactura == null) {
                            ModeloFacturacion::mdlActualizarTalonarioBoleta($serieDestino);
                        } else {
                            ModeloFacturacion::mdlActualizarTalonarioFactura($serieDestino);
                        }

                        $cuentas = self::ctrRegistrarCuentaCorriente($codigo, $tipoCuenta, $docDest, $usuario, $usureg, $pcreg);
                    }

                    break;
                case "01":
                    $tipo = "S03";
                    $nombreTipo = "FACTURA";
                    $stock = ModeloArticulos::mdlActualizarStockPedido($codigo, $almacen);
                    $serie = substr($documento, 0, 4);
                    ModeloFacturacion::mdlActualizarTalonarioFactura($serie);
                    $cuentas = self::ctrRegistrarCuentaCorriente($codigo, $tipoDocumento, $documento, $usuario, $usureg, $pcreg);
                    break;
                case "03":
                    $tipo = "S02";
                    $nombreTipo = "BOLETA";
                    $stock = ModeloArticulos::mdlActualizarStockPedido($codigo, $almacen);
                    $serie = substr($documento, 0, 4);
                    ModeloFacturacion::mdlActualizarTalonarioBoleta($serie);
                    $cuentas = self::ctrRegistrarCuentaCorriente($codigo, $tipoDocumento, $documento, $usuario, $usureg, $pcreg);
                    break;
                case "09":
                    $tipo = "S70";
                    $nombreTipo = "PROFORMA";
                    $stock = ModeloArticulos::mdlActualizarStockPedido($codigo, $almacen);
                    $serie = substr($documento, 0, 3);
                    ModeloFacturacion::mdlActualizarTalonarioProforma($serie);
                    $cuentas = self::ctrRegistrarCuentaCorriente($codigo, $tipoDocumento, $documento, $usuario, $usureg, $pcreg);
                    break;
                case "07":
                    $tipo = "E05";
                    $nombreTipo = "NC";
                    $stock = ModeloArticulos::mdlActualizarStockPedidoB($codigo, $almacen);
                    $serie = substr($documento, 0, 4);
                    ModeloFacturacion::mdlActualizarNotaSerie("nota_credito", "serie_nc", $serie);
                    $nota = self::ctrRegistrarNotaCredito($documento, $tipNota, $origenVenta, $fechaOrigen, $notaMotivo, $usuario);
                    break;
                default:
                    throw new Exception("Tipo de documento desconocido.");
            }

            if ($stock == "ok") {
                $movimientos = self::ctrRegistrarMovimientos($codigo, $tipo, $documento, $cliente, $vendedor, $dscto, $nombreTipo, $codigoAlmacen);
                $ventas = self::ctrRegistrarVentas($codigo, $tipo, $documento, $docDest, $docOrigen, $usuario, $nombreTipo, $usureg, $pcreg, $chofer, $movilidad, $peso, $bultos, $exportacion, $tipo_moneda);

                ModeloFacturacion::mdlActualizarPedidoF($codigo);
                ModeloFacturacion::mdlActualizarPedidoB($codigo);


                if ($checkBoleta == "on" || $checkFactura == "on") {
                    self::ctrConfirmacion($tipo, $documento, $tipoDestino, $docDest);
                } else {
                    self::ctrConfirmacion($tipo, $documento, null, null);
                }
            }
        }
    }

    static public function ctrRegistrarMovimientos($codigo, $tipo, $documento, $cliente, $vendedor, $dscto, $nombreTipo, $codigoAlmacen)
    {
        date_default_timezone_set("America/Lima");
        $fecha = date("Y-m-d");

        $respuesta = ModeloPedidos::mdlMostraDetallesTemporal("detalle_temporal", $codigo);
        $detalle = "";
        foreach ($respuesta as $key => $value) {
            $total = ($tipo == "E05") ?  ($value["cantidad"] * $value["precio"] * ((100 - $dscto) / 100)) * -1 : $value["cantidad"] * $value["precio"] * ((100 - $dscto) / 100);
            $cantidad = ($tipo == "E05") ? -$value["cantidad"] : $value["cantidad"];

            $detalle .= "('" . $tipo . "','" . $documento . "','" . $fecha . "','" . $value["articulo"] . "','" . $cliente . "','" . $vendedor . "'," . $cantidad . "," . $value["precio"] . ",0," . $dscto . "," . $total . ",'" . $nombreTipo . "','" . $codigoAlmacen . "')";

            if ($key < count($respuesta) - 1) {
                $detalle .= ",";
            }
        }

        $rptMovimientos = ModeloFacturacion::mdlRegistrarMovimientos($detalle);

        return $rptMovimientos;
    }

    static public function ctrRegistrarVentas($codigo, $tipo, $documento, $docDest, $docOrigen, $usuario, $nombreTipo, $usureg, $pcreg, $chofer, $movilidad, $peso, $bultos, $esportacion, $tipo_moneda)
    {
        $respuestaDoc = ModeloPedidos::mdlMostraPedidosCabecera($codigo);

        $signo = $tipo == "E05" ? -1 : 1;

        $datosD = array(
            "tipo" => $tipo,
            "documento" => $documento,
            "neto" => $signo * $respuestaDoc["op_gravada"],
            "igv" => $signo * $respuestaDoc["igv"],
            "dscto" => $signo * $respuestaDoc["descuento_total"],
            "total" => $signo * $respuestaDoc["total"],
            "cliente" => $respuestaDoc["cod_cli"],
            "vendedor" => $respuestaDoc["vendedor"],
            "agencia" => $respuestaDoc["agencia"],
            "lista_precios" => $respuestaDoc["lista"],
            "condicion_venta" => $respuestaDoc["condicion_venta"],
            "doc_destino" => $docDest,
            "doc_origen" => $docOrigen,
            "usuario" => $usuario,
            "tipo_documento" => $nombreTipo,
            "cuenta" => "",
            "usureg" => $usureg,
            "pcreg" => $pcreg,
            "chofer" => $chofer,
            "carro" => $movilidad,
            "peso" => $peso,
            "bultos" => $bultos,
            "exportacion" => $esportacion,
            "tipo_moneda" => $tipo_moneda
        );

        $respuestaVentas = ModeloFacturacion::mdlRegistrarDocumento($datosD);

        return $respuestaVentas;
    }

    static public function ctrRegistrarCuentaCorriente($codigo, $tipoDocumento, $documento, $usuario, $usureg, $pcreg)
    {
        $respuestaDoc = ModeloPedidos::mdlMostraPedidosCabecera($codigo);

        date_default_timezone_set("America/Lima");
        $fecha = date("Y-m-d");
        $fecha_ven = date("Y-m-d", strtotime($fecha . "+ " .  $respuestaDoc["dias"] . " day"));

        $datos = array(
            "tipo_doc" => $tipoDocumento,
            "num_cta" => $documento,
            "cliente" => $respuestaDoc["cod_cli"],
            "vendedor" => $respuestaDoc["vendedor"],
            "fecha_ven" => $fecha_ven,
            "monto" => $respuestaDoc["total"],
            "cod_pago" => $tipoDocumento,
            "usuario" => $usuario,
            "saldo" => $respuestaDoc["total"],
            "usureg" => $usureg,
            "pcreg" => $pcreg
        );

        $respuestaCuenta = ModeloFacturacion::mdlGenerarCtaCte($datos);

        return $respuestaCuenta;
    }

    static public function ctrRegistrarNotaCredito($documento, $tipNota, $origenVenta, $fechaOrigen, $notaMotivo, $usuario)
    {
        $arregloNota = array(
            "tipo" => 'E05',
            "documento" => $documento,
            "tipo_doc" => $tipNota,
            "doc_origen" => $origenVenta,
            "fecha_origen" => $fechaOrigen,
            "motivo" => $notaMotivo,
            "tip_cont" => 'NTCD',
            "observacion" => '',
            "usuario" => $usuario
        );

        $notaCredito = ModeloFacturacion::mdlIngresarNotaCD($arregloNota);
        return $notaCredito;
    }

    static public function ctrConfirmacion($tipo, $documento, $tipoAdicional = null, $documentoAdicional = null)
    {
        $impresion = (substr($documento, 0, 1) == "0") ? "guia_remision" : "impresion_guia";

        $config = array(
            "S01" => array(
                "title" => "Se Genero la Guia de Remisión ",
                "url" => "vistas/reportes_ticket/$impresion.php?codigo="
            ),
            "S02" => array(
                "title" => "Se Genero la Boleta ",
                "url" => "vistas/reportes_ticket/impresion_bolfact.php?tipo=S02&documento="
            ),
            "S03" => array(
                "title" => "Se Genero la Factura ",
                "url" => "vistas/reportes_ticket/impresion_bolfact.php?tipo=S03&documento="
            ),
            "S70" => array(
                "title" => "Se Genero la Proforma ",
                "url" => "extensiones/tcpdf/pdf/reporte_proforma.php?tipo=S70&documento="
            ),
            "E05" => array(
                "title" => "Se Genero la Nota cred. ",
                "url" => ""
            )
        );

        $urls = [];
        if ($tipoAdicional && isset($config[$tipoAdicional]) && $documentoAdicional) {
            $urls[] = $config[$tipoAdicional]["url"] . $documentoAdicional;
            $config[$tipoAdicional]["title"] .= $documentoAdicional;
        }

        if (isset($config[$tipo])) {
            $urls[] = $config[$tipo]["url"] . $documento;
            $config[$tipo]["title"] .= $documento;
            echo self::generateConfirmationScript($config[$tipo]["title"], $urls);
        } else {
            throw new Exception("Tipo de documento desconocido.");
        }
    }

    static private function generateConfirmationScript($title, array $urls)
    {
        $script = '<script>
            swal({
                type: "success",
                title: "' . $title . '",
                showConfirmButton: true,
                confirmButtonText: "Cerrar"
            }).then(function (result) {
                if (result.value) {';

        foreach ($urls as $url) {
            if (!empty($url)) {
                $script .= 'window.open("' . $url . '", "_blank");';
            }
        }

        $script .= 'window.location = "pedidoscv";
                    }
                });
            </script>';

        return $script;
    }

    static public function ctrActualizarGuiaRemision()
    {
        if (isset($_POST["codPedidoC"])) {

            $datos = array(
                "tipo" => "S01",
                "documento" => $_POST["codPedidoC"],
                "chofer" => $_POST["chofer"],
                "carro" => $_POST["carro"],
                "peso" => $_POST["peso"],
                "bultos" => $_POST["bultos"],
            );

            $respuesta = ModeloFacturacion::mdlActualizarGuiaRemision($datos);

            $impresion = (substr($_POST["codPedidoC"], 0, 1) == "0") ? "guia_remision" : "impresion_guia";

            if ($respuesta == "ok") {

                self::ctrConfirmacion("S01", $_POST["codPedidoC"], null, null);
            }
        }
    }
}
