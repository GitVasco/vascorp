<?php
class ControladorAlmacenCorte
{

    /*
    * SACAR EL ULTIMO CODIGO
    */
    static public function ctrUltimoCodigoAC()
    {

        $respuesta = ModeloAlmacenCorte::mdlUltimoCodigoAC();

        return $respuesta;
    }

    static public function ctrSiguienteCodigoAC()
    {

        return ModeloAlmacenCorte::mdlSiguienteCodigoAC();
    }

    /*
	* MOSTRAR ARTICULOS EN ORDENES DE CORTE PARA EL ALMACEN CORTE
	*/
    static public function ctrMostarArticulosOrdCorte()
    {

        $respuesta = ModeloAlmacenCorte::mdlMostarArticulosOrdCorte();

        return $respuesta;
    }

    static private function alertaAlmacenCorte($tipo, $titulo, $texto, $ruta)
    {
        echo '<script>
            swal({
                type: "' . $tipo . '",
                title: "' . $titulo . '",
                text: "' . $texto . '",
                showConfirmButton: true,
                confirmButtonText: "Cerrar"
            }).then((result)=>{
                if(result.value){
                    window.location="' . $ruta . '";}
            });
        </script>';
    }

    /*
    * CREAR ALMACEN DE CORTE
    */
    static public function ctrCrearAlmacenCorte()
    {

        if (
            !isset($_POST["nuevaAlmacenCorte"]) ||
            !isset($_POST["idUsuario"]) ||
            !isset($_POST["listaArticulosAC"])
        ) {
            return;
        }

        if ($_POST["listaArticulosAC"] == "") {
            self::alertaAlmacenCorte(
                "error",
                "Error",
                "¡No se seleccionó ningún artículo. Por favor, intenteló de nuevo!",
                "crear-almacencorte"
            );
            return;
        }

        $guia = isset($_POST["nuevaGuia"]) ? trim((string) $_POST["nuevaGuia"]) : "";

        if ($guia === "") {
            self::alertaAlmacenCorte(
                "error",
                "Error",
                "¡Debe ingresar la guía de corte!",
                "crear-almacencorte"
            );
            return;
        }

        if (ModeloAlmacenCorte::mdlExisteGuiaAC($guia)) {
            self::alertaAlmacenCorte(
                "error",
                "Guía duplicada",
                "¡Esa guía ya fue registrada. No se puede crear el corte otra vez!",
                "crear-almacencorte"
            );
            return;
        }

        $codigo = ModeloAlmacenCorte::mdlSiguienteCodigoAC();
        while (ModeloAlmacenCorte::mdlExisteCodigoAC($codigo)) {
            $codigo++;
        }

        $listArticulo = json_decode($_POST["listArticulo"], true);
        $listaArticulosAC = json_decode($_POST["listaArticulosAC"], true);

        if (!is_array($listArticulo) || !is_array($listaArticulosAC)) {
            self::alertaAlmacenCorte(
                "error",
                "Error",
                "¡No se pudo leer el detalle de artículos. Por favor, intenteló de nuevo!",
                "crear-almacencorte"
            );
            return;
        }

        $articulos_array = [];
        foreach ($listArticulo as $valor) {

            $articulo = $valor["articulo"];

            if (!in_array($articulo, $articulos_array)) {

                $articulos_array[] = $articulo;
            }
        }

        $resultado = [];
        foreach ($articulos_array as $unico_id) {

            $temporal = [];
            foreach ($listArticulo as $valor) {

                $id = $valor["articulo"];

                if ($id === $unico_id) {

                    $temporal[] = $valor;
                }
            }

            $producto = $temporal[0];

            $producto["cantidad"] = 0;
            foreach ($temporal as $producto_temporal) {

                $producto["cantidad"] = $producto["cantidad"] + $producto_temporal["cantidad"];
            }

            $resultado[] = $producto;
        }

        foreach ($resultado as $value) {

            $valor = $value["articulo"];

            $valor1 = $value["cantidad"];

            ModeloAlmacenCorte::mdlActualizarAlmCorte($valor, $valor1);
        }

        foreach ($resultado as $value) {

            $valor = $value["articulo"];

            $valor1 = $value["cantidad"];

            ModeloAlmacenCorte::mdlActualizarOrdCorte($valor, $valor1);
        }

        foreach ($listaArticulosAC as $value) {

            $valor = $value["articulo"];

            $valor1 = $value["ordencorte"];

            $valor2 = $value["cantidad"];

            $valor3 = $value["cerrar"];

            if ($valor3 == "NO") {

                ModeloAlmacenCorte::mdlActualizarSaldoOrdCorteB($valor, $valor1, $valor2);
            } else {
                ModeloAlmacenCorte::mdlActualizarSaldoOrdCorte($valor, $valor1, $valor2);
            }
        }

        $datos = array(
            "codigo" => $codigo,
            "guia" => $guia,
            "usuario" => $_POST["idUsuario"],
            "total" => $_POST["totalAlmacenCorte"],
            "estado" => "1"
        );

        $respuesta = ModeloAlmacenCorte::mdlGuardarAlmacenCorte($datos);

        if ($respuesta == "ok") {

            foreach ($listaArticulosAC as $value) {

                $datosD = array(
                    "almacencorte" => $codigo,
                    "ordcorte" => $value["ordencorte"],
                    "idocd" => $value["idocd"],
                    "articulo" => $value["articulo"],
                    "cantidad" => $value["cantidad"]
                );

                ModeloAlmacenCorte::mdlGuardarDetallesAlmacenCorte($datosD);
            }

            ModeloAlmacenCorte::mdlGuardarDetallesAlmacenCorteMP($codigo);

            ModeloAlmacenCorte::mdlActualizarOrdCorteSaldo();

            ModeloAlmacenCorte::mdlActualizarSaldoOrdCorteGral();

            ModeloAlmacenCorte::mdlActualizarOrdCorteEstadoParcial();

            ModeloAlmacenCorte::mdlActualizarOrdCorteEstadoCerrado();

            self::alertaAlmacenCorte(
                "success",
                "Felicitaciones",
                "¡La información fue registrada con éxito!",
                "almacencorte"
            );
        } else {

            self::alertaAlmacenCorte(
                "error",
                "Error",
                "¡La información presento problemas y no se registro adecuadamente. Por favor, intenteló de nuevo!",
                "crear-almacencorte"
            );
        }
    }

    /*
    * MOSTRAR DATOS DE ALMACEN DE CORTE
    */
    static public function ctrMostrarAlmacenCorte($valor)
    {

        $respuesta = ModeloAlmacenCorte::mdlMostrarAlmacenCorte($valor);

        return $respuesta;
    }

    /*
    * MOSTRAR DETALLES DE ALMACEN DE CORTE
    */
    static public function ctrMostrarDetallesAlmacenCorte($item, $valor)
    {

        $tabla = "almacencorte_detallejf";

        $respuesta = ModeloAlmacenCorte::mdlMostarDetallesAlmacenCorte($tabla, $item, $valor);

        return $respuesta;
    }

    /* 
	* VISUALIZAR DATOS DEL CORTE DETALLE
	*/
    static public function ctrVisualizarAlmacenCorteDetalle($valor)
    {

        $respuesta = ModeloAlmacenCorte::mdlVisualizarAlmacenCorteDetalle($valor);

        return $respuesta;
    }

    /*=============================================
	RANGO FECHAS
	=============================================*/

    static public function ctrRangoFechasAlmacenCortes($fechaInicial, $fechaFinal)
    {

        $tabla = "almacencortejf";

        $respuesta = ModeloAlmacenCorte::mdlRangoFechasAlmacenCortes($tabla, $fechaInicial, $fechaFinal);

        return $respuesta;
    }

    static public function ctrRangoFechasVerCortes($fechaInicial, $fechaFinal)
    {

        $tabla = "almacencortejf";

        $respuesta = ModeloAlmacenCorte::mdlRangoFechasVerCortes($tabla, $fechaInicial, $fechaFinal);

        return $respuesta;
    }

    /*=============================================
	RANGO FECHAS DE CONSUMO DE TELAS
	=============================================*/

    static public function ctrRangoFechasConsumoTelas($fechaInicial, $fechaFinal)
    {


        $respuesta = ModeloAlmacenCorte::mdlRangoFechasConsumoTelas($fechaInicial, $fechaFinal);

        return $respuesta;
    }


    /*
    * MOSTRAR DATOS DE ALMACEN DE CORTE
    */
    static public function ctrMostrarTelasAlmacenCorte($valor)
    {

        $respuesta = ModeloAlmacenCorte::mdlMostrarTelasAlmacenCorte($valor);

        return $respuesta;
    }


    /*=============================================
	EDITAR SECTORES
	=============================================*/

    static public function ctrEditarTelaCorte()
    {

        if (isset($_POST["almacencorteMP"])) {
            $telasInput = $_POST["telas"];
            for ($i = 0; $i < count($telasInput); $i++) {
                $datosNotaSalida = array(
                    "SalVta" => $_POST["cantidadMP" . $i],
                    "Nro" => $_POST["notaSalidaMP" . $i],
                    "CodPro" => $_POST["materia" . $i]
                );

                $actualizarSaldo = ModeloNotasSalidas::mdlActualizarSaldoNotaSalida("venta_det", $datosNotaSalida);

                $datos = array(
                    "codigo" => $_POST["almacencorteMP"],
                    "cantidad" => $_POST["cantidadMP" . $i],
                    "diferencia" => $_POST["diferenciaMP" . $i],
                    "entrega" => $_POST["entregaMP" . $i],
                    "merma" => $_POST["mermaMP" . $i],
                    "mp_sinuso" => $_POST["sinusoMP" . $i],
                    "materia" => $_POST["materia" . $i],
                    "nota_salida" => $_POST["notaSalidaMP" . $i]
                );

                $respuesta = ModeloAlmacenCorte::mdlIngresarTelaCorte($datos);
            }

            if ($respuesta == "ok") {

                echo '<script>

					swal({
						  type: "success",
						  title: "La tela del corte ha sido cambiado correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "almacencorte";

									}
								})

					</script>';
            }
        }
    }

    /*=============================================
	EDITAR SECTORES
	=============================================*/

    static public function ctrEditarNotificacionCorte()
    {

        if (isset($_POST["almacencorteNot"])) {
            $telasInput = $_POST["telasNot"];
            for ($i = 0; $i < count($telasInput); $i++) {

                $datos = array(
                    "codigo" => $_POST["almacencorteNot"],
                    "notificacion" => $_POST["notificacionMP" . $i],
                    "materia" => $_POST["materiaNot" . $i]
                );

                $respuesta = ModeloAlmacenCorte::mdlIngresarNotificacionCorte($datos);
            }

            if ($respuesta == "ok") {

                echo '<script>

					swal({
						  type: "success",
						  title: "La tela del corte ha sido cambiado correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "almacencorte";

									}
								})

					</script>';
            }
        }
    }


    /*
    * EDITAR ALMACEN DE CORTE
    */
    static public function ctrEditarAlmacenCorte()
    {

        /*
        todo: ver si trae datos
        */
        if (
            isset($_POST["editarAlmacenCorte"]) &&
            isset($_POST["idUsuario"]) &&
            isset($_POST["listaArticulosAC"])
        ) {

            #var_dump("nuevoAlmacenCorte", $_POST["nuevaAlmacenCorte"]);
            #var_dump("idUsuario", $_POST["idUsuario"]);
            #var_dump("listaArticulosAC", $_POST["listaArticulosAC"]);

            if ($_POST["listaArticulosAC"] == "") {

                /*
                    ? Mostramos una alerta suave si viene vacia
                    */
                echo '<script>
                            swal({
                                type: "error",
                                title: "Error",
                                text: "¡No se realizo ningún cambio. Por favor, intenteló de nuevo!",
                                showConfirmButton: true,
                                confirmButtonText: "Cerrar"
                            }).then((result)=>{
                                if(result.value){
                                    window.location = "index.php?ruta=editar-almacencorte&codigo=' . $_POST["editarAlmacenCorte"] . '";}
                            });
                        </script>';
            } else {

                #var_dump("listaArticulosAC", $_POST["listaArticulosAC"]);

                //RECUPERAMOS TODO COMO ANTES DE SU CREACION DEL CORTE
                $item = "almacencorte";
                $valor = $_POST["editarAlmacenCorte"];
                $listaArticuloCorte = ControladorAlmacenCorte::ctrMostrarDetallesAlmacenCorte($item, $valor);

                // var_dump($listaArticuloCorte);

                foreach ($listaArticuloCorte as $key => $value) {

                    $valor = $value["articulo"];

                    $valor1 = $value["ordencorte"];

                    $valor2 = $value["cantidad"];

                    ModeloAlmacenCorte::mdlRecuperarAlmCorte($valor, $valor2);

                    ModeloAlmacenCorte::mdlRecuperarOrdCorte($valor, $valor2);

                    $descontarSaldo = ModeloAlmacenCorte::mdlRecuperarSaldoOrdCorte($valor, $valor1, $valor2);

                    // var_dump($descontarSaldo);

                }
                /*
                    ? Capturamos los articulos unicos y sumamos sus cantidades
                    */

                $listArticulo = json_decode($_POST["listArticulo"], true);
                #var_dump("listArticulo", $listArticulo);

                /*
                    * array on los articulos unicos sin repetir
                    */
                $articulos_array = [];
                foreach ($listArticulo as $valor) {

                    $articulo = $valor["articulo"];

                    if (!in_array($articulo, $articulos_array)) {

                        $articulos_array[] = $articulo;
                    }
                }
                #var_dump("articulos_array", $articulos_array);

                /*
                    * crear un array con la lista unica
                    */
                $resultado = [];
                foreach ($articulos_array as $unico_id) {

                    $temporal = [];
                    $cantidad = 0;
                    foreach ($listArticulo as $valor) {

                        $id = $valor["articulo"];

                        if ($id === $unico_id) {

                            $temporal[] = $valor;
                        }
                    }

                    $producto = $temporal[0];

                    $producto["cantidad"] = 0;
                    foreach ($temporal as $producto_temporal) {

                        $producto["cantidad"] = $producto["cantidad"] + $producto_temporal["cantidad"];
                    }
                    // dx($producto["cantidad"]); // trace

                    // store unique productoo with updated quantity
                    $resultado[] = $producto;
                }
                #var_dump("resultado", $resultado);

                /*
                    todo: GUARDAMOS LOS TOTALES DEL CORTE EN ARTICULO
                    */
                foreach ($resultado as $value) {

                    $valor = $value["articulo"];

                    $valor1 = $value["cantidad"];

                    ModeloAlmacenCorte::mdlActualizarAlmCorte($valor, $valor1);
                }

                /*
                    todo: DESCONTAMOS LOS TOTALES DEL CORTE EN ARTICULO - ORDEN DE CORTE
                    */
                foreach ($resultado as $value) {

                    $valor = $value["articulo"];

                    $valor1 = $value["cantidad"];

                    ModeloAlmacenCorte::mdlActualizarOrdCorte($valor, $valor1);

                    ModeloAlmacenCorte::mdlIngresarCantCorte($valor, $valor1);
                }

                /*
                    todo: Actualizamos saldos de las Detalles de Ordenes de Corte
                    */

                $listaArticulosAC = json_decode($_POST["listaArticulosAC"], true);
                #var_dump("listaArticulosAC", $listaArticulosAC);

                foreach ($listaArticulosAC as $value) {

                    $valor = $value["articulo"];

                    $valor1 = $value["ordencorte"];

                    $valor2 = $value["cantidad"];

                    ModeloAlmacenCorte::mdlActualizarSaldoOrdCorte($valor, $valor1, $valor2);
                }

                /*
                    todo: Actualizamos saldos de las ordenes de corte y estados
                    */
                ModeloAlmacenCorte::mdlActualizarSaldoOrdCorteGral();

                ModeloAlmacenCorte::mdlActualizarOrdCorteEstadoParcial();

                ModeloAlmacenCorte::mdlActualizarOrdCorteEstadoCerrado();

                /*
                    todo: Editar cabeera de ALMACEN DE CORTE
                    */
                $datos = array(
                    "codigo" => $_POST["editarAlmacenCorte"],
                    "guia" => $_POST["editarGuia"],
                    "usuario" => $_POST["idUsuario"],
                    "total" => $_POST["totalAlmacenCorte"],
                    "estado" => "1"
                );
                //var_dump("datos", $datos);

                $respuesta = ModeloAlmacenCorte::mdlEditarAlmacenCorte($datos);
                #var_dump("respuesta", $respuesta);

                /* 
                    todo: Editamos los cambios del detalle almacencorte, primero eliminamos los detalles de almacencorte y de almacenmp
                    */

                $eliminarDato = ModeloIngresos::mdlEliminarDato("almacencorte_detallejf", "almacencorte", $_POST["editarAlmacenCorte"]);
                $eliminarDatoMP = ModeloIngresos::mdlEliminarDato("almacencorte_detalle_mpjf", "almacencorte", $_POST["editarAlmacenCorte"]);
                #$respuesta = "no";

                if ($respuesta == "ok") {

                    /*
                        todo: Guardar detalle de almacen de corte
                        */
                    #var_dump("ultimoId", $ultimoId);

                    foreach ($listaArticulosAC as $key => $value) {

                        $datosD = array(
                            "almacencorte" => $_POST["editarAlmacenCorte"],
                            "ordcorte" => $value["ordencorte"],
                            "idocd" => $value["idocd"],
                            "articulo" => $value["articulo"],
                            "cantidad" => $value["nuevaCantidad"]
                        );
                        // var_dump("datosD", $datosD);

                        ModeloAlmacenCorte::mdlGuardarDetallesAlmacenCorte($datosD);
                    }

                    ModeloAlmacenCorte::mdlGuardarDetallesAlmacenCorteMP($_POST["editarAlmacenCorte"]);

                    # Mostramos una alerta suave
                    echo '<script>
                                swal({
                                    type: "success",
                                    title: "Felicitaciones",
                                    text: "¡La información fue editada con éxito!",
                                    showConfirmButton: true,
                                    confirmButtonText: "Cerrar"
                                }).then((result)=>{
                                    if(result.value){
                                        window.location="almacencorte";}
                                });
                            </script>';
                } else {

                    # Mostramos una alerta suave
                    echo '<script>
                                swal({
                                    type: "error",
                                    title: "Error",
                                    text: "¡La información presento problemas y no se registro adecuadamente. Por favor, intenteló de nuevo!",
                                    showConfirmButton: true,
                                    confirmButtonText: "Cerrar"
                                }).then((result)=>{
                                    if(result.value){
                                        window.location = "index.php?ruta=editar-almacencorte&codigo=' . $_POST["editarAlmacenCorte"] . '";}
                                });
                            </script>';
                }
            }
        }
    }
}
