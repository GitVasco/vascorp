<?php

class ControladorOrdenCorte
{

    /* 
    * MOSTRAR DATOS DE LAS ORDENES DE CORTE
    */
    static public function ctrMostrarOrdenCorte($item, $valor)
    {

        $tabla = "ordencortejf";

        $respuesta = ModeloOrdenCorte::mdlMostarOrdenCorte($tabla, $item, $valor);

        return $respuesta;
    }

    /* 
    * SACAR EL ULTIMO CODIGO
    */
    static public function ctrUltimoCodigoOC()
    {

        $tabla = "ordencortejf";

        $respuesta = ModeloOrdenCorte::mdlUltimoCodigoOC($tabla);

        return $respuesta;
    }

    /* 
    * CREAR ORDEN DE CORTE
    */
    static public function ctrCrearOrdenCorte()
    {

        /* 
        todo: Verificamos que traiga datos
        */
        if (
            isset($_POST["nuevaOrdenCorte"]) &&
            isset($_POST["idUsuario"]) &&
            isset($_POST["configuracion"])
        ) {

            #var_dump("nuevaOrdenCorte", $_POST["nuevaOrdenCorte"]);

            if ($_POST["listaArticulosOC"] == "") {

                ModeloOrdenCorte::mdlEliminarArticulo();

                /* 
                    ? Mostramos una alerta suave si viene vacia
                    */
                echo '<script>
                            swal({
                                type: "error",
                                title: "Error",
                                text: "¡No se seleccionó ningún artículo. Por favor, intenteló de nuevo!",
                                showConfirmButton: true,
                                confirmButtonText: "Cerrar"
                            }).then((result)=>{
                                if(result.value){
                                    window.location="crear-ordencorte";}
                            });
                        </script>';
            } else {

                /* 
                    ? Actualizamos la cantidad de la orden de corte
                    */

                $listaArticulos = json_decode($_POST["listaArticulosOC"], true);
                /* 
                    * GUARDAR LA ORDEN DE CORTE
                    */

                $datos = array(
                    "usuario" => $_POST["idUsuario"],
                    "codigo" => $_POST["nuevaOrdenCorte"],
                    "configuracion" => $_POST["configuracion"],
                    "total" => $_POST["totalOrdenCorte"],
                    "saldo" => $_POST["totalOrdenCorte"],
                    "estado" => "Pendiente"
                );

                #var_dump("datos", $datos);

                $respuesta = ModeloOrdenCorte::mdlGuardarOrdenCorte("ordencortejf", $datos);

                if ($respuesta == "ok") {

                    /* 
                        * GUARDAR DETALLE DE ORDEN DE CORTE
                        */

                    $ultimoId = ModeloOrdenCorte::mdlUltimoId();
                    #var_dump("ultimoId", $ultimoId[0]["ult_codigo"]);

                    foreach ($listaArticulos as $key => $value) {

                        $datosD = array(
                            "ordencorte" => $ultimoId[0]["ult_codigo"],
                            "articulo" => $value["articulo"],
                            "cantidad" => $value["cantidad"],
                            "saldo" => $value["cantidad"]
                        );

                        #var_dump("datosD", $datosD);
                        ModeloOrdenCorte::mdlGuardarDetallesOrdenCorte("detalles_ordencortejf", $datosD);
                        ModeloArticulos::mdlSumarOrdCorte($value["cantidad"], $value["articulo"]);
                    }
                    ModeloOrdenCorte::mdlEliminarArticulo();
                    # Mostramos una alerta suave
                    echo '<script>
                        swal({
                            type: "success",
                            title: "Felicitaciones",
                            text: "¡La información fue registrada con éxito!",
                            showConfirmButton: true,
                            confirmButtonText: "Cerrar"
                        }).then((result)=>{
                            if(result.value){
                                window.location="ordencorte";}
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
									window.location="crear-ordencorte";}
							});
						</script>';
                }
            }
        }
    }

    /*
     * CREAR ORDEN DE CORTE DESDE CSV (código,cantidad)
     * Retorna "ok" o mensaje de error (string)
     */
    static public function ctrCrearOrdenCorteDesdeCSV()
    {
        if (!isset($_POST["listaArticulosOC"]) || $_POST["listaArticulosOC"] === "") {
            return "No se recibió la lista de artículos.";
        }
        if (!isset($_SESSION["id"])) {
            return "Sesión no válida.";
        }

        $listaArticulos = json_decode($_POST["listaArticulosOC"], true);
        if (!is_array($listaArticulos) || count($listaArticulos) === 0) {
            return "La lista de artículos está vacía o no es válida.";
        }

        $configuracion = controladorArticulos::ctrConfiguracion();
        $urgencia = isset($configuracion["urgencia"]) ? $configuracion["urgencia"] : 0;

        $ult_codigo = ModeloOrdenCorte::mdlUltimoCodigoOC("ordencortejf");
        $codigo = $ult_codigo ? (int)$ult_codigo[0]["ultimo_codigo"] + 1 : 1001;

        $total = 0;
        $listaValida = array();
        $articulosNoEncontrados = array();

        foreach ($listaArticulos as $item) {
            $articulo = trim(isset($item["articulo"]) ? $item["articulo"] : "");
            $cantidad = isset($item["cantidad"]) ? (int)$item["cantidad"] : 0;
            if ($articulo === "" || $cantidad < 1) {
                continue;
            }
            $existe = ModeloArticulos::mdlMostrarArticulos($articulo);
            if (!$existe) {
                $articulosNoEncontrados[] = $articulo;
                continue;
            }
            $listaValida[] = array(
                "articulo" => $articulo,
                "cantidad" => $cantidad
            );
            $total += $cantidad;
        }

        if (count($articulosNoEncontrados) > 0) {
            return "Artículos no encontrados: " . implode(", ", array_slice($articulosNoEncontrados, 0, 10)) . (count($articulosNoEncontrados) > 10 ? "..." : "");
        }
        if (count($listaValida) === 0) {
            return "No hay filas válidas (código y cantidad positiva).";
        }

        $datos = array(
            "usuario" => $_SESSION["id"],
            "codigo" => $codigo,
            "configuracion" => $urgencia,
            "total" => $total,
            "saldo" => $total,
            "estado" => "Pendiente"
        );

        $respuesta = ModeloOrdenCorte::mdlGuardarOrdenCorte("ordencortejf", $datos);
        if ($respuesta !== "ok") {
            return "Error al guardar la orden de corte.";
        }

        $ultimoId = ModeloOrdenCorte::mdlUltimoId();
        $idOrden = (int)$ultimoId[0]["ult_codigo"];

        foreach ($listaValida as $value) {
            $datosD = array(
                "ordencorte" => $idOrden,
                "articulo" => $value["articulo"],
                "cantidad" => $value["cantidad"],
                "saldo" => $value["cantidad"]
            );
            ModeloOrdenCorte::mdlGuardarDetallesOrdenCorte("detalles_ordencortejf", $datosD);
            ModeloArticulos::mdlSumarOrdCorte($value["cantidad"], $value["articulo"]);
        }

        ModeloOrdenCorte::mdlEliminarArticulo();
        return "ok";
    }

    /* 
	* MOSTRAR DATOS DEL DETALLE DE LAS TARJETAS
	*/
    static public function ctrMostrarDetallesOrdenCorte($item, $valor)
    {

        $tabla = "detalles_ordencortejf";

        $respuesta = ModeloOrdenCorte::mdlMostraDetallesOrdenCorte($tabla, $item, $valor);

        return $respuesta;
    }

    /* 
	* MOSTRAR DATOS DEL DETALLE DE LAS TARJETAS
	*/
    static public function ctrMostrarDetalleOrdenCorte($item, $valor)
    {

        $tabla = "detalles_ordencortejf";

        $respuesta = ModeloOrdenCorte::mdlMostraDetalleOrdenCorte($tabla, $item, $valor);

        return $respuesta;
    }
    /* 
    * Editar Orden de Corte
    */
    static public function ctrEditarOrdenCorte()
    {

        if (isset($_POST["editarCodigo"]) && isset($_POST["idUsuario"]) && isset($_POST["listaArticulosOC"])) {

            #var_dump($_POST["editarCodigo"], $_POST["idUsuario"],$_POST["listaArticulosOC"]);

            if ($_POST["listaArticulosOC"] == "") {

                echo '<script>
						swal({
							type: "error",
							title: "Error",
							text: "¡No se cambio ninguna materia prima. Por favor, intenteló de nuevo!",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
						}).then((result)=>{
							if(result.value){
								window.location="index.php?ruta=editar-ordencorte&codigo=' . $_POST["codigoE"] . '";}
						});
					</script>';
            } else {

                /* 
                todo: Traemos los datos del detalle de Orden de Corte
                */
                $detaOC = ModeloOrdenCorte::mdlMostraDetallesOrdenCorte("detalles_ordencortejf", "ordencorte", $_POST["editarCodigo"]);
                #var_dump("detaOC", $detaOC);

                /* 
                todo: Cabiamos los codigos de al lista por los codigos de articulos
                */
                foreach ($detaOC as $key => $value) {

                    $infoArt = controladorArticulos::ctrMostrarArticulos($value["articulo"]);
                    $detaOC[$key]["articulo"] = $infoArt["articulo"];
                    #var_dump("detaOC", $detaOC[$key]["articulo"]);

                }

                if ($_POST["listaArticulosOC"] == "") {

                    $listaArticulosOC = $detaOC;
                    $validarCambio = false;
                } else {

                    $listaArticulosOC = json_decode($_POST["listaArticulosOC"], true);
                    $validarCambio = true;
                }

                if ($validarCambio) {

                    /* 
                    todo: Actualizamos en articulos las ord_Corte
                    */
                    foreach ($listaArticulosOC as $key => $value) {

                        $item1 = "ord_corte";
                        $valor1 = $value["ord_corte"];
                        $valor2 = $value["articulo"];

                        ModeloArticulos::mdlActualizarUnDato("articulojf", $item1, $valor1, $valor2);
                    }
                }

                /* 
                todo: Editamos los cambios de la cabecera Orden de Corte
                */
                $datos = array(
                    "codigo" => $_POST["editarCodigo"],
                    "usuario" => $_POST["idUsuario"],
                    "total" => $_POST["totalOrdenCorte"],
                    "saldo" => $_POST["totalOrdenCorte"],
                    "lastUpdate" => $_POST["fechaActual"]
                );
                #var_dump("datos", $datos);

                $respuesta = ModeloOrdenCorte::mdlEditarOrdenCorte("ordencortejf", $datos);

                if ($respuesta == "ok") {

                    /* 
                    todo: Editamos los cambios del detalle Ordenes de Corte, primero eliminamos los detalles
                    */

                    $eliminarDato = ModeloOrdenCorte::mdlEliminarDato("detalles_ordencortejf", "ordencorte", $_POST["editarCodigo"]);

                    $eliminarDato = "ok";

                    if ($eliminarDato == "ok") {

                        foreach ($listaArticulosOC as $key => $value) {

                            #var_dump("listaArticulosOC", $listaArticulosOC);

                            $datosD = array(
                                "ordencorte" => $_POST["editarCodigo"],
                                "articulo" => $value["articulo"],
                                "cantidad" => $value["cantidad"],
                                "saldo" => $value["cantidad"]
                            );

                            #var_dump("datosD", $datosD);

                            ModeloOrdenCorte::mdlGuardarDetallesOrdenCorte("detalles_ordencortejf", $datosD);
                        }

                        # Mostramos una alerta suave
                        echo '<script>
                                swal({
                                    type: "success",
                                    title: "Felicitaciones",
                                    text: "¡La información fue Actualizada con éxito!",
                                    showConfirmButton: true,
                                    confirmButtonText: "Cerrar"
                                }).then((result)=>{
                                    if(result.value){
                                        window.location="ordencorte";}
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
									window.location="ordencorte";}
							});
						</script>';
                    }
                } else {

                    echo '<script>
						swal({
							type: "error",
							title: "Error",
							text: "¡La información presento problemas y no se actualizó adecuadamente. Por favor, intenteló de nuevo!",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
						}).then((result)=>{
							if(result.value){
								window.location="ordencorte";}
						});
					</script>';
                }
            }
        }
    }

    /* 
    *Método para eliminar las ordenes de corte
    */
    static public function ctrEliminarOrdenCorte($codigo)
    {

        $item = "codigo";
        $infoOC = ModeloOrdenCorte::mdlMostraDetallesOrdenCorte("ordencortejf", $item, $codigo);
        #var_dump("infoOC", $infoOC);

        $detaOC = ModeloOrdenCorte::mdlMostraDetallesOrdenCorte("detalles_ordencortejf", "ordencorte", $codigo);
        #var_dump("detaOC", $detaOC);

        /* 
        todo: Actualizamos orden de corte en Articulojf
        */
        foreach ($detaOC as $key => $value) {

            $valorA = $value["articulo"];
            #var_dump("valorA", $valorA);

            $infoA = ModeloArticulos::mdlMostrarArticulos($valorA);
            #var_dump("infoA", $infoA);
            #var_dump("infoA", $infoA["ord_corte"]);
            #var_dump("cantidad", $value["cantidad"]);

            $ord_corte = $infoA["ord_corte"] - $value["cantidad"];
            #var_dump("ord_corte", $ord_corte);

            ModeloArticulos::mdlActualizarUnDato("articulojf", "ord_corte", $ord_corte, $value["articulo"]);
        }

        /* 
        todo: Eliminamos la cabecera de Orden de corte
        */
        $tablaOC = "ordencortejf";
        $itemOC = "codigo";
        $valorOC = $codigo;

        $respuesta = ModeloOrdenCorte::mdlEliminarDato($tablaOC, $itemOC, $valorOC);

        if ($respuesta == "ok") {

            /* 
            todo: Eliminamos el detalle de Orden de corte
            */
            $tablaDOC = "detalles_ordencortejf";
            $itemDOC = "ordencorte";
            $valorDOC = $codigo;

            ModeloOrdenCorte::mdlEliminarDato($tablaDOC, $itemDOC, $valorDOC);
        }

        return $respuesta;
    }

    /* 
	* VISUALIZAR DATOS DE LA ORDEN DE CORTE - CABECERA
	*/
    static public function ctrVisualizaOrdenCorte($item, $valor)
    {

        $tabla = "ordencortejf";

        $respuesta = ModeloOrdenCorte::mdlVisualizaOrdenCorte($tabla, $item, $valor);

        return $respuesta;
    }

    /* 
	* VISUALIZAR DATOS DE LA ORDEN DE CORTE DETALLE
	*/
    static public function ctrVisualizarOrdenCorteDetalle($item, $valor)
    {

        $tabla = "detalles_ordencortejf";

        $respuesta = ModeloOrdenCorte::mdlVisualizarOrdenCorteDetalle($tabla, $item, $valor);

        return $respuesta;
    }


    /* 
	* VISUALIZAR DATOS DE LA ORDEN DE CORTE DETALLE
	*/
    static public function ctrVisualizarOrdenCorteDetalleCantidad($item, $valor)
    {

        $tabla = "detalles_ordencortejf";

        $respuesta = ModeloOrdenCorte::mdlVisualizarOrdenCorteDetalleCantidad($tabla, $item, $valor);

        return $respuesta;
    }

    /*=============================================
	RANGO FECHAS
	=============================================*/

    static public function ctrRangoFechasOrdenCortes($fechaInicial, $fechaFinal)
    {

        $tabla = "ordencortejf";

        $respuesta = ModeloOrdenCorte::mdlRangoFechasOrdenCortes($tabla, $fechaInicial, $fechaFinal);

        return $respuesta;
    }
    static public function ctrRangoFechasOrdenCortesGeneral($fechaInicial, $fechaFinal)
    {

        $tabla = "detalles_ordencortejf";

        $respuesta = ModeloOrdenCorte::mdlRangoFechasOrdenCortesGeneral($tabla, $fechaInicial, $fechaFinal);

        return $respuesta;
    }

    static public function ctrRangoFechasOrdenCortesCantidad($fechaInicial, $fechaFinal)
    {

        $tabla = "detalles_ordencortejf";

        $respuesta = ModeloOrdenCorte::mdlRangoFechasOrdenCortesCantidad($tabla, $fechaInicial, $fechaFinal);

        return $respuesta;
    }

    /* 
	* MOSTRAR ORDEN DE CORTE PENDIENTES Y ABIERTOS
	*/
    static public function ctrOCPend()
    {

        $respuesta = ModeloOrdenCorte::mdlOCPend();

        return $respuesta;
    }
    /* 
    * CREAR ORDEN DE CORTE
    */
    static public function ctrCrearDetalleOrdenCorte()
    {
        if (
            isset($_POST["nuevoCodigo"]) &&
            isset($_POST["articulo"]) &&
            isset($_POST["cantidad"])
        ) {

            $codigo = $_GET["codigo"];
            $datosD = array(
                "ordencorte" => $_POST["nuevoCodigo"],
                "articulo" => $_POST["articulo"],
                "cantidad" => $_POST["cantidad"],
                "saldo" => $_POST["cantidad"]
            );

            $respuesta = ModeloOrdenCorte::mdlGuardarDetallesOrdenCorte("detalles_ordencortejf", $datosD);

            if ($respuesta == "ok") {

                $datos = array(
                    "codigo" => $_GET["codigo"],
                    "usuario" => $_POST["idUsuario"],
                    "lastUpdate" => $_POST["fechaActual"]
                );
                // var_dump($datos);
                ModeloOrdenCorte::mdlAgregarCantidadOC($datos);

                ModeloArticulos::mdlSumOc($_POST["articulo"], $_POST["cantidad"]);

                echo '<script>
                                swal({
                                    type: "success",
                                    title: "Felicitaciones",
                                    text: "¡La información fue Actualizada con éxito!",
                                    showConfirmButton: true,
                                    confirmButtonText: "Cerrar"
                                }).then((result)=>{
                                    if(result.value){
                                        window.location="index.php?ruta=editar-detalle-ordencorte&codigo=' . $codigo . '";}
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
                            window.location="index.php?ruta=editar-detalle-ordencorte&codigo=' . $codigo . '";}
                    });
                </script>';
            }
        }
    }

    /* 
    * CREAR ORDEN DE CORTE
    */
    static public function ctrEditarDetalleOrdenCorte()
    {

        if (
            isset($_POST["idDetalle"]) &&
            isset($_POST["editarArticulo"]) &&
            isset($_POST["editarCantidad"]) &&
            isset($_POST["cambio"])
        ) {

            $codigo = $_GET["codigo"];

            $datosD = array(
                "id" => $_POST["idDetalle"],
                "cantidad" => $_POST["editarCantidad"],
                "saldo" => $_POST["editarCantidad"]
            );

            $respuesta = ModeloOrdenCorte::mdlEditarDetalleOrdenCorte("detalles_ordencortejf", $datosD);


            if ($respuesta == "ok") {

                $datos = array(
                    "codigo" => $_GET["codigo"],
                    "usuario" => $_POST["idUsuario"],
                    "cambio" => $_POST["cambio"],
                    "lastUpdate" => $_POST["fechaActual"]
                );
                //var_dump($datos);
                ModeloOrdenCorte::mdlEditarCantidadOC($datos);

                ModeloArticulos::mdlActualizarOrdenCorte($_POST["editarArticulo"], $_POST["cambio"]);


                echo '<script>
                                swal({
                                    type: "success",
                                    title: "Felicitaciones",
                                    text: "¡La información fue Actualizada con éxito!",
                                    showConfirmButton: true,
                                    confirmButtonText: "Cerrar"
                                }).then((result)=>{
                                    if(result.value){
                                        window.location="index.php?ruta=editar-detalle-ordencorte&codigo=' . $codigo . '";}
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
                            window.location="index.php?ruta=editar-detalle-ordencorte&codigo=' . $codigo . '";}
                    });
                </script>';
            }
        }
    }
    static public function ctrEliminarDetalleOrdenCorte()
    {
        if (isset($_GET["idDetalle"])) {

            $item = "id";
            $valor = $_GET["id"];
            $codigo = $_GET["codigo"];

            $respuesta = ModeloOrdenCorte::mdlEliminarDato("detalles_ordencortejf", $item, $valor);

            if ($respuesta == "ok") {

                ModeloArticulos::mdlSumOc($_GET["idDetalle"], $_GET["cantidad"]);
                // var_dump($_GET["idDetalle"], $_GET["cantidad"]);

                echo '<script>
                            swal({
                                type: "success",
                                title: "Felicitaciones",
                                text: "¡La información fue eliminada con éxito!",
                                showConfirmButton: true,
                                confirmButtonText: "Cerrar"
                            }).then((result)=>{
                                if(result.value){
                                    window.location="index.php?ruta=editar-detalle-ordencorte&codigo=' . $codigo . '";}
                            });
                        </script>';
            }
        }
    }
}
