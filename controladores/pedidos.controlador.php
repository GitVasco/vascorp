<?php

class ControladorPedidos
{

    /*
    * MOSTRAR CABECERA DE TEMPORAL
    */
    static public function ctrMostrarTemporal($valor)
    {

        $tabla = "temporaljf";

        $respuesta = ModeloPedidos::mdlMostrarTemporal($tabla, $valor);

        return $respuesta;
    }

    /*
    * Pedidos pendentes reporte
    */
    static public function ctrPedidosPendientes($vendedor)
    {

        $respuesta = ModeloPedidos::mdlPedidosPendientes($vendedor);

        return $respuesta;
    }

    /*
    * MOSTRAR CABECERA DE TEMPORAL TOTAL
    */
    static public function ctrMostrarTemporalTotal($valor)
    {

        $respuesta = ModeloPedidos::mdlMostrarTemporalTotal($valor);

        return $respuesta;
    }

    /*
    *MOSTRAR DETALLE DE TAMPOERAL
    */
    static public function ctrMostrarDetallesTemporal($valor)
    {

        $tabla = "detalle_temporal";

        $respuesta = ModeloPedidos::mdlMostraDetallesTemporal($tabla, $valor);

        return $respuesta;
    }

    /*
    *MOSTRAR DETALLE DE TAMPOERAL B
    */
    static public function ctrMostrarDetallesTemporalB($valor)
    {

        $respuesta = ModeloPedidos::mdlMostraDetallesTemporalB($valor);

        return $respuesta;
    }

    /* 
    * CREAR ARTICULOS EN EL PEDIDO
    */

    static public function ctrCrearPedido()
    {

        if (isset($_POST["pedido"])) {

            /*
            todo: VARIABLES GLOBALES DEL TALONARIO
            */
            $tabla = "temporaljf";

            #ver si ya existe el pedido
            $pedido = ModeloPedidos::mdlMostrarTemporal($tabla, $_POST["pedido"]);
            #var_dump("pedido", $pedido);

            if ($pedido["codigo"] != "") { #si ya existe

                /*
                todo: GUARDAR EL DETALLE TEMPORAL - CUANDO YA EXISTE EL TEMPORAL
                */
                $valor = $_POST["modeloModalA"];
                $respuesta = controladorArticulos::ctrVerArticulosB($valor);

                foreach ($respuesta as $value) {

                    $articulo = $value["articulo"];
                    #var_dump("articulo", $value["articulo"]);
                    $tabla = "detalle_temporal";
                    $val1 = $articulo;
                    $val2 = $_POST[$articulo];
                    $val3 = $_POST["pedido"];
                    $val4 = $_POST["precioA"];

                    #1ero eliminar si ya se registro
                    $eliminar = array(
                        "codigo" => $val3,
                        "articulo" => $val1
                    );

                    $limpiar = ModeloPedidos::mdlEliminarDetalleTemporal($tabla, $eliminar);

                    if ($val2 > 0) {


                        // var_dump("eliminar", $eliminar);
                        #var_dump("limpiar", $limpiar);

                        $datos = array(
                            "codigo"    => $val3,
                            "articulo"  => $val1,
                            "cantidad"  => $val2,
                            "precio"    => $val4
                        );
                        #var_dump("datos", $datos);

                        $respuestaB = ModeloPedidos::mdlGuardarTemporalDetalle($tabla, $datos);
                        #var_dump("respuesta", $respuesta);

                    }
                }

                if ($respuestaB = "ok") {

                    echo '  <script>                                        

                    Command: toastr["success"]("El modelo fue registrado");
                    $("#updDiv").load(" #updDiv");//actualizas el div
                    $("#updDivB").load(" #updDivB");//actualizas el div
                    $("#updDivC").load(" #updDivC");//actualizas el div

                </script>';
                }
            } else { #si no existe

                #vemos el numero que sigue en el talonario y actualizamos en +1
                $numero = ControladorMovimientos::ctrMostrarTalonario();
                $talonario = $numero["pedido"] + 1;
                ModeloPedidos::mdlActualizarTalonario();

                $usuario = $_POST["usuario"];
                $talonarioN = $usuario . $talonario;

                /*
                todo: GUARDAR CABECERA
                */
                $datos = array(
                    "codigo" => $talonarioN,
                    "cliente" => $_POST["clienteA"],
                    "vendedor" => $_POST["vendedorA"],
                    "lista" => $_POST["nLista"],
                    "usuario" => $_POST["usuario"],
                    "agencia" => $_POST["agenciaA"]
                );
                // var_dump($datos);

                ModeloPedidos::mdlGuardarTemporal($tabla, $datos);

                /*
                todo: GUARDAR EL DETALLE TEMPORAL
                */
                $valor = $_POST["modeloModalA"];
                $respuesta = controladorArticulos::ctrVerArticulos($valor);

                foreach ($respuesta as $value) {

                    $articulo = $value["articulo"];
                    #var_dump("articulo", $value["articulo"]);
                    $tabla = "detalle_temporal";
                    $val1 = $articulo;
                    $val2 = $_POST[$articulo];
                    $val3 = $talonarioN;
                    $val4 = $_POST["precioA"];

                    if ($val2 > 0) {

                        $datos = array(
                            "codigo"    => $val3,
                            "articulo"  => $val1,
                            "cantidad"  => $val2,
                            "precio"    => $val4
                        );
                        #var_dump("datos", $datos);

                        $respuesta = ModeloPedidos::mdlGuardarTemporalDetalle($tabla, $datos);
                        #var_dump("respuesta", $respuesta);

                        if ($respuesta = "ok") {

                            echo '  <script>

                                        Command: toastr["success"]("El modelo fue registrado");
                                        window.location="index.php?ruta=crear-pedidocv&pedido=' . $talonarioN . '";

                                    </script>';
                        }
                    }
                }
            }
        }
    }


    /* 
    *CREAR CONDICIONES DE VENTA Y TOTALES
    */
    static public function ctrCrearPedidoTotales()
    {

        if (isset($_POST["nuevoCodigo"])) {


            /*
            * ACTUALIZAMOS LOS TOTALES DEL PEDIDO
            */
            $grav = floatval($_POST["nuevoSubTotalA"]);
            if ($grav > 0) {
                $dscto = round(floatval($_POST["descTotal"]) / $grav * 100, 2);
            } else {
                $dscto = 0;
            }
            $datos = array(
                "cliente" => $_POST["seleccionarCliente"],
                "codigo" => $_POST["nuevoCodigo"],
                "op_gravada" => $_POST["nuevoSubTotalA"],
                "descuento_total" => $_POST["descTotal"],
                "sub_total" => $_POST["subTotal"],
                "impuesto" => $_POST["impTotal"],
                "total" => $_POST["nuevoTotal"],
                "vendedor" => $_POST["seleccionarVendedor"],
                "usuario" => $_POST["idUsuario"],
                "condicion_venta" => $_POST["condicionVenta"],
                "agencia" => $_POST["agencia"],
                "dscto" => $dscto
            );

            $respuestaA = ModeloPedidos::mdlActualizarTotalesPedido($datos);
            //$respuestaB = ModeloPedidos::mdlEliminarDetalleTemporalTotal($datos);
            //var_dump($respuesta);

            if ($respuestaA == "ok") {

                //ModeloPedidos::mdlActualizarTotalPedido();

                ModeloPedidos::mdlActualizarTotales($_POST["nuevoCodigo"]);

                # Mostramos una alerta suave
                echo '<script>
                         Command: toastr["success"]("El pedido fue registrado");
                                window.location="pedidoscv";
                    </script>';
            } else {

                var_dump("no llego aqui");
            }
        }
    }

    /*
    * MOSTRAR CABECERA DE TEMPORAL
    */
    static public function ctrMostraPedidosCabecera($valor)
    {

        $respuesta = ModeloPedidos::mdlMostraPedidosCabecera($valor);

        return $respuesta;
    }

    /*
    * MOSTRAR CABECERA DE TEMPORAL - GENERAL
    */
    static public function ctrMostraPedidosGeneral($valor)
    {

        $respuesta = ModeloPedidos::mdlMostraPedidosGeneral($valor);

        return $respuesta;
    }

    /*
    * MOSTRAR CABECERA DE TEMPORAL - EXCLUYE VENDEDORES 08, 06, 23, 99
    */
    static public function ctrMostraPedidosGeneralVendedores()
    {

        $respuesta = ModeloPedidos::mdlMostraPedidosGeneralVendedores();

        return $respuesta;
    }

    /*
    * MOSTRAR TABLAS
    */
    static public function ctrMostraPedidosTablas($valor)
    {

        $respuesta = ModeloPedidos::mdlMostraPedidosTablas($valor);

        return $respuesta;
    }

    /*
    * MOSTRAR PEDIDO CON FORMATO DE IMRPESION
    */
    static public function ctrPedidoImpresion($codigo, $modelo)
    {

        $respuesta = ModeloPedidos::mdlPedidoImpresion($codigo, $modelo);

        return $respuesta;
    }

    /*
    * MOSTRAR PEDIDO CON FORMATO DE IMRPESION
    */
    static public function ctrPedidoImpresionB($codigo, $ini, $fin)
    {

        $respuesta = ModeloPedidos::mdlPedidoImpresionB($codigo, $ini, $fin);

        return $respuesta;
    }

    /*
    * MOSTRAR PEDIDO CON FORMATO DE IMRPESION - MODELOS
    */
    static public function ctrPedidoImpresionMod($valor)
    {

        $respuesta = ModeloPedidos::mdlPedidoImpresionMod($valor);

        return $respuesta;
    }

    /*
    * MOSTRAR PEDIDO CON FORMATO DE IMRPESION - CABECERA
    */
    static public function ctrPedidoImpresionCab($valor)
    {

        $respuesta = ModeloPedidos::mdlPedidoImpresionCab($valor);

        return $respuesta;
    }

    /*
    * MOSTRAR PEDIDO CON FORMATO DE IMRPESION - TOTALES GENERALES
    */
    static public function ctrPedidoImpresionTotales($valor)
    {

        $respuesta = ModeloPedidos::mdlPedidoImpresionTotales($valor);

        return $respuesta;
    }

    /*
    * DIVIDIR PEDIDO
    */
    static public function ctrDividirPedido()
    {

        if (isset($_POST["codPedidoD"])) {


            $pedido = $_POST["codPedidoD"];
            $porcentaje = $_POST["perPed"];

            $respuesta = ModeloPedidos::mdlDividirPedidoD($pedido, $porcentaje);

            $respC = ModeloPedidos::mdlPedidoImpresionCab($pedido);

            $cabecera = ModeloPedidos::mdlDetalleToalDiv($pedido);
            //var_dump($cabecera);

            $op_gravada = $cabecera["total"];
            $dscto = round($cabecera["total"] * $respC["dscto"] / 100, 2);
            $subTotal = $op_gravada - $dscto;
            $igv = round($subTotal * 0.18, 2);
            $total = $subTotal + $igv;

            //var_dump($op_gravada, $dscto, $subTotal, $igv, $total);

            $respuestaCab = ModeloPedidos::mdlActualizarDiv($pedido, $op_gravada, $dscto, $subTotal, $igv, $total);

            //var_dump($respuestaCab);

            if ($respuestaCab == "ok") {

                # Mostramos una alerta suave
                echo '<script>

                swal({
                      type: "success",
                      title: "El pedido ha sido actualizaddo",
                      showConfirmButton: true,
                      confirmButtonText: "Cerrar"
                      }).then(function(result){
                                if (result.value) {

                                window.location = "pedidoscv";

                                }
                            })

                </script>';
            } else {

                var_dump("no llego aqui");
            }
        }
    }

    static public function ctrEnviarPedido()
    {

        if (isset($_POST["fechaEnvio"])) {

            //var_dump($_POST["fechaEnvio"]);
            $fecha = $_POST["fechaEnvio"];

            $cabecera = ModeloPedidos::mdlMostrarTemporalFecha($fecha);
            //var_dump($cabecera);

            $rutaCab = "vistas/pedidos/" . $fecha . ".txt";

            $archivoCab = fopen($rutaCab, "w");

            fwrite($archivoCab, "F|" . $fecha . "|" . $_SESSION["id"] . "\r\n");

            foreach ($cabecera as $key => $value) {

                if ($key < count($cabecera) - 1) {

                    fwrite($archivoCab, $value["cabecera"] . "\r\n");
                } else {

                    fwrite($archivoCab, $value["cabecera"]);
                }
            }

            fclose($archivoCab);

            echo '<script>

            swal({

                type: "success",
                title: "Se genero archivos",
                showConfirmButton: true,
                confirmButtonText: "Cerrar"
                }).then(function(result){

                    if (result.value) {

                    window.location = "pedidoscv";

                    }

                })

            </script>';
        }
    }


    static public function ctrLeerPedido()
    {
        if (isset($_POST["importPedTxt"])) {
            $filename = $_FILES['archivoPedTxt']['name'];

            if (!empty($filename)) {
                $ruta = "vistas/pedidos/leer/";
                $filepath = $ruta . basename($filename);

                if (move_uploaded_file($_FILES['archivoPedTxt']['tmp_name'], $filepath)) {
                    $contenido = file_get_contents($filepath);
                    $lineas = explode("\n", $contenido);

                    $detalleEntries = [];
                    $fecha = "";
                    $vend = "";

                    $unique_codes = array_unique(array_column(array_map(function ($line) {
                        return explode("|", $line);
                    }, $lineas), 1));

                    $existentCodeArrays = ModeloPedidos::mdlMostrarTemporalMultiple("temporaljf", $unique_codes);
                    $existentCodes = array_column($existentCodeArrays, 'codigo');

                    foreach ($lineas as $key => $value) {
                        $partes = explode("|", $value);

                        if (isset($partes[1]) && !in_array($partes[1], $existentCodes)) {
                            if ($partes[0] === "D") {
                                // $detalleEntries[] = "(" . implode(",", array_slice($partes, 1, 5)) . ")";

                                // Agregar comillas simples alrededor de los elementos de texto
                                $detalleWithQuotes = array_map(function ($elem, $index) {
                                    return $index == 1 ? "'" . $elem . "'" : $elem;
                                }, array_slice($partes, 1, 5), array_keys(array_slice($partes, 1, 5)));

                                $detalleEntries[] = "(" . implode(",", $detalleWithQuotes) . ")";
                            } elseif ($partes[0] === "C") {
                                $datosC = array(
                                    "codigo"            => $partes[1],
                                    "cliente"           => $partes[2],
                                    "vendedor"          => $partes[3],
                                    "lista"             => $partes[4],
                                    "op_gravada"        => $partes[5],
                                    "descuento_total"   => $partes[6],
                                    "sub_total"         => $partes[7],
                                    "igv"               => $partes[8],
                                    "total"             => $partes[9],
                                    "condicion_venta"   => $partes[10],
                                    "estado"            => $partes[11],
                                    "fecha"             => $partes[12],
                                    "usuario"           => $partes[13],
                                    "agencia"           => $partes[14],
                                    "usuario_estado"    => $partes[15],
                                    "dscto"             => $partes[16]
                                );

                                $respuestaC = ModeloPedidos::mdlLeerPedidoC($datosC);
                            } elseif ($partes[0] === "F") {
                                $fecha = $partes[1];
                                $vend = $partes[2];
                            }
                        }
                    }

                    $detalleString = implode(",", $detalleEntries);

                    $respuestaD = ModeloPedidos::mdlLeerPedidoD($detalleString);

                    // Mostrar mensaje de éxito y redirigir
                    echo '<script>
                        window.open("vistas/reportes_ticket/pedidos_prov.php?fecha=' . $fecha . '&vend=' . trim($vend) . '","_blank");
                        swal({
                            type: "success",
                            title: "Los pedidos han sido ingresados",
                            showConfirmButton: true,
                            confirmButtonText: "Cerrar"
                        }).then(function(result){
                            if (result.value) {
                                window.location = "pedidoscv";
                            }
                        });
                    </script>';
                } else {
                    // Mostrar mensaje de error
                    echo '<script>
                            swal({
                                type: "error",
                                title: "¡Error al subir el archivo!",
                                showConfirmButton: true,
                                confirmButtonText: "Cerrar"
                            }).then(function(result){
                                if (result.value) {
                                    window.location = "pedidoscv";
                                }
                            });
                    </script>';
                }
            } else {
                // Mostrar mensaje de error
                echo '<script>
                    swal({
                        type: "error",
                        title: "¡Error, debe seleccionar un archivo!",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar"
                    }).then(function(result){
                        if (result.value) {
                            window.location = "pedidoscv";
                        }
                    });
                </script>';
            }
        }
    }



    /*
    * MOSTRAR CABECERA DE TEMPORAL
    */
    static public function ctrMostrarTemporalFecVen($fecha, $vend)
    {

        $respuesta = ModeloPedidos::mdlMostrarTemporalFecVen($fecha, $vend);

        return $respuesta;
    }

    /* 
    *ANULAR PEDIDO
    */
    static public function ctrAnularPedido()
    {

        if (isset($_GET["codigoP"])) {

            $codigo = $_GET["codigoP"];
            //var_dump("AQUI",$codigo);

            $pedido = ModeloPedidos::mdlMostraPedidosCabecera($codigo);
            //var_dump($pedido);

            $respuesta = ModeloFacturacion::mdlActualizarPedido($codigo, "ANULADO", $_SESSION["id"]);
            #var_dump($respuesta);

            $reiniciar = ModeloPedidos::mdlReiniciarPedido();
            #var_dump($reiniciar);

            $contar = ModeloPedidos::mdlCantAprobados();
            #var_dump($contar);

            if ($contar == "ok") {

                echo '<script>

                swal({
                    type: "success",
                    title: "El pedido ha sido anulada correctamente",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar",
                    closeOnConfirm: false
                    }).then(function(result){
                                if (result.value) {

                                window.location = "pedidoscv";

                                }
                            })

                </script>';
            }
        }
    }

    /*
    * MOSTRAR COTIZACIÓN
    */
    static public function ctrMostrarCotizacion($codigo)
    {

        $respuesta = ModeloPedidos::mdlMostrarCotizacion($codigo);

        return $respuesta;
    }


    //*TRAER CUENTAS
    static public function ctrTraerCuentas($valor)
    {

        $respuesta = ModeloPedidos::mdlTraerCuentas($valor);

        return $respuesta;
    }
}
