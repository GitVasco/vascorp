<?php

class ControladorIngresos
{

    /* 
    * MOSTRAR DATOS DE INGRESO CABECERA
    */
    static public function ctrMostrarIngresos($item, $valor)
    {

        $tabla = "movimientos_cabecerajf";

        $respuesta = ModeloIngresos::mdlMostarIngresos($tabla, $item, $valor);

        return $respuesta;
    }

    /* 
    * MOSTRAR DETALLE DE INGRESO
    */
    static public function ctrMostrarDetallesIngresos($item, $valor)
    {

        $tabla = "movimientosjf_2026";

        $respuesta = ModeloIngresos::mdlMostarDetallesIngresos($tabla, $item, $valor);

        return $respuesta;
    }

    /* 
    * MOSTRAR DATOS DE LAS ARTICULOS O CIERRES PARA CREAR INGRESO
    */
    static public function ctrMostrarArticulosCierres($idCierre)
    {

        $respuesta = ModeloArticulos::mdlMostrarArticulosCierres($idCierre);

        return $respuesta;
    }

    // VISUALIZAR INGRESO DETALLE
    static public function ctrVisualizarIngresoDetalle($valor)
    {

        $respuesta = ModeloIngresos::mdlVisualizarIngresoDetalle($valor);

        return $respuesta;
    }

    /* 
    * CREAR INGRESO
    */
    static public function ctrCrearIngreso()
    {

        /* 
        todo: Verificamos que traiga datos
        */
        if (
            isset($_POST["nuevoTalleres"]) &&
            isset($_POST["idUsuario"])
        ) {

            #var_dump("nuevaOrdenCorte", $_POST["nuevaOrdenCorte"]);

            if ($_POST["listaArticulosIngreso"] == "") {

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
                                    window.location="crear-ingresos";}
                            });
                        </script>';
            } else {

                /* 
                    ? Actualizamos la cantidad de la orden de corte
                    */

                $listaArticulos = json_decode($_POST["listaArticulosIngreso"], true);

                #var_dump("listaArticulos", $listaArticulos);

                if ($_POST["nuevoTipoSector"] == "0") {
                    foreach ($listaArticulos as $value) {

                        $tabla = "articulojf";

                        $valor = $value["articulo"];

                        //Actualizamos Taller
                        $item1 = "taller";
                        $valor1 = $value["taller"];

                        ModeloArticulos::mdlActualizarUnDato($tabla, $item1, $valor1, $valor);

                        //Actualizamos Stock
                        $item2 = "stock";
                        $valor2 = $value["cantidad"];

                        ModeloArticulos::mdlActualizarStockIngreso($valor, $valor2);
                    }
                } else {
                    foreach ($listaArticulos as $value) {

                        $tabla = "cierres_detallejf";

                        $valor = $value["idCierre"];
                        $articulo = $value["articulo"];

                        //Actualizar Taller
                        $item1 = "cantidad";
                        $valor1 = $value["taller"];

                        ModeloArticulos::mdlActualizarUnCierre($tabla, $item1, $valor1, $valor);

                        //Actualizamos Stock
                        $item2 = "stock";
                        $valor2 = $value["cantidad"];

                        ModeloArticulos::mdlActualizarStockIngreso($articulo, $valor2);

                        //Actualizamos servicio

                        ModeloArticulos::mdlActualizarArticuloServicio($articulo, $valor2);
                    }
                }



                /* 
                    * GUARDAR EL INGRESO
                    */
                $fecha = $_POST["nuevaFecha"];
                $datos = array(
                    "tipo" => "E20",
                    "usuario" => $_POST["idUsuario"],
                    "guia" => $_POST["nuevaGuiaIng"],
                    "taller" => $_POST["nuevoTalleres"],
                    "documento" => $_POST["nuevoCodigo"],
                    "total" => $_POST["totalTaller"],
                    "fecha" => $fecha,
                    "almacen" => "01"
                );

                #var_dump("datos", $datos);

                $respuesta = ModeloIngresos::mdlGuardarIngreso("movimientos_cabecerajf", $datos);

                if ($respuesta == "ok") {


                    foreach ($listaArticulos as $key => $value) {

                        $datosD = array(
                            "tipo" => "E20",
                            "documento" => $_POST["nuevoCodigo"],
                            "taller" => $_POST["nuevoTalleres"],
                            "fecha" => $fecha,
                            "articulo" => $value["articulo"],
                            "cantidad" => $value["cantidad"],
                            "almacen" => "01",
                            "idcierre" => $value["idCierre"]
                        );

                        #var_dump("datosD", $datosD);

                        ModeloIngresos::mdlGuardarDetalleIngreso("movimientosjf_2026", $datosD);
                    }

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
                                        window.location="ingresos";}
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
									window.location="crear-ingresos";}
							});
						</script>';
                }
            }
        }
    }

    /* 
    * CREAR SEGUNDA
    */
    static public function ctrCrearSegunda()
    {

        /* 
        todo: Verificamos que traiga datos
        */
        if (
            isset($_POST["nuevoTalleres"]) &&
            isset($_POST["idUsuario"])
        ) {

            #var_dump("nuevaOrdenCorte", $_POST["nuevaOrdenCorte"]);

            if ($_POST["listaArticulosIngreso"] == "") {

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
                                    window.location="crear-segunda";}
                            });
                        </script>';
            } else {

                /* 
                    ? Actualizamos la cantidad de taller en articulos
                    */

                $listaArticulos = json_decode($_POST["listaArticulosIngreso"], true);

                $entaller = self::ActualizarEnTaller($listaArticulos);

                foreach ($entaller as $id => $cantidad) {
                    $idEntrada = $id;
                    $cantidadRestante = $cantidad;

                    ModeloIngresos::mdlActualizarSaldoEnTaller($idEntrada, $cantidadRestante);
                }

                #var_dump("listaArticulos", $listaArticulos);

                if ($_POST["nuevoTipoSector"] == "0") {
                    foreach ($listaArticulos as $value) {

                        $tabla = "articulojf";

                        $valor = $value["articulo"];

                        //Actualizamos Taller
                        $item1 = "taller";
                        $valor1 = $value["taller"];

                        ModeloArticulos::mdlActualizarUnDato($tabla, $item1, $valor1, $valor);
                    }
                } else {
                    foreach ($listaArticulos as $value) {

                        $tabla = "cierres_detallejf";

                        $valor = $value["idCierre"];
                        $articulo = $value["articulo"];

                        //Actualizar Taller
                        $item1 = "cantidad";
                        $valor1 = $value["taller"];

                        ModeloArticulos::mdlActualizarUnCierre($tabla, $item1, $valor1, $valor);


                        $valor2 = $value["cantidad"];
                        //Actualizamos servicio

                        ModeloArticulos::mdlActualizarArticuloServicio($articulo, $valor2);
                    }
                }
                /* 
                    * GUARDAR EL INGRESO
                    */
                $fecha = new DateTime();
                $datos = array(
                    "tipo" => $_POST["tipoMovimiento"],
                    "usuario" => $_POST["idUsuario"],
                    "taller" => $_POST["nuevoTalleres"],
                    "guia" => $_POST["nuevaGuiaIng"],
                    "documento" => $_POST["nuevoCodigo"],
                    "total" => $_POST["totalTaller"],
                    "fecha" => $_POST["nuevaFecha"],
                    "almacen" => "02",
                    "trabajador" => $_POST["nuevoTrabajadores"]
                );

                #var_dump("datos", $datos);

                $respuesta = ModeloIngresos::mdlGuardarSegunda("movimientos_cabecerajf", $datos);

                if ($respuesta == "ok") {


                    foreach ($listaArticulos as $key => $value) {

                        $datosD = array(
                            "tipo" => $_POST["tipoMovimiento"],
                            "documento" => $_POST["nuevoCodigo"],
                            "taller" => $_POST["nuevoTalleres"],
                            "fecha" => $_POST["nuevaFecha"],
                            "articulo" => $value["articulo"],
                            "cliente" => $_POST["nuevoTrabajadores"],
                            "cantidad" => $value["cantidad"],
                            "almacen" => "02",
                            "idcierre" => $value["idCierre"]
                        );

                        #var_dump("datosD", $datosD);

                        ModeloIngresos::mdlGuardarDetalleSegunda("movimientosjf_2026", $datosD);
                    }

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
                                        window.location="ingresos";}
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
									window.location="crear-segunda";}
							});
						</script>';
                }
            }
        }
    }

    /* 
    * Editar Ingreso
    */
    static public function ctrEditarIngreso()
    {

        if (isset($_POST["idUsuario"]) && isset($_POST["listaArticulosIngreso"])) {

            #var_dump($_POST["editarCodigo"], $_POST["idUsuario"],$_POST["listaArticulosOC"]);

            if ($_POST["listaArticulosIngreso"] == "") {

                echo '<script>
						swal({
							type: "error",
							title: "Error",
							text: "¡No se cambio ninguna materia prima. Por favor, intenteló de nuevo!",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
						}).then((result)=>{
							if(result.value){
								window.location="index.php?ruta=editar-ingreso";}
						});
					</script>';
            } else {

                /* 
                todo: Traemos los datos del detalle de ingreso
                */
                $detaOC = ModeloIngresos::mdlMostarDetallesIngresos("movimientosjf_2026", "documento", $_POST["editarCodigo"]);
                #var_dump("detaOC", $detaOC);

                /* 
                todo: Cabiamos los codigos de al lista por los codigos de articulos
                */
                foreach ($detaOC as $key => $value) {

                    $infoArt = controladorArticulos::ctrMostrarArticulos($value["articulo"]);
                    $detaOC[$key]["articulo"] = $infoArt["articulo"];
                    #var_dump("detaOC", $detaOC[$key]["articulo"]);

                }

                if ($_POST["listaArticulosIngreso"] == "") {

                    $listaArticulosOC = $detaOC;
                    $validarCambio = false;
                } else {

                    $listaArticulosOC = json_decode($_POST["listaArticulosIngreso"], true);
                    $validarCambio = true;
                }

                if ($validarCambio) {

                    /* 
                    todo: Actualizamos en articulos de ingresos
                    */
                    if ($_POST["editarTipoSector"] == "0") {
                        foreach ($listaArticulosOC as $value) {

                            $tabla = "articulojf";

                            $valor = $value["articulo"];

                            //Actualizamos Taller
                            $item1 = "taller";
                            $valor1 = $value["taller"];

                            ModeloArticulos::mdlActualizarUnDato($tabla, $item1, $valor1, $valor);

                            //Actualizamos Stock
                            $item2 = "stock";
                            $valor2 = $value["cantidad"];

                            ModeloArticulos::mdlActualizarStockIngreso($valor, $valor2);
                        }
                    } else {
                        foreach ($listaArticulosOC as $value) {

                            $tabla = "cierres_detallejf";

                            $valor = $value["idCierre"];
                            $articulo = $value["articulo"];

                            //Actualizar Taller
                            $item1 = "cantidad";

                            $valor1 = $value["taller"];

                            ModeloArticulos::mdlActualizarUnCierre($tabla, $item1, $valor1, $valor);

                            //Actualizamos Stock
                            $item2 = "stock";
                            $valor2 = $value["cantidad"];

                            ModeloArticulos::mdlActualizarStockIngreso($articulo, $valor2);

                            //Actualizamos servicio

                            ModeloArticulos::mdlActualizarArticuloServicio($articulo, $valor2);
                        }
                    }
                }
                $fecha = new DateTime();
                /* 
                todo: Editamos los cambios de la cabecera ingreso
                */
                $datos = array(
                    "id" => $_POST["idIngreso"],
                    "documento" => $_POST["editarCodigo"],
                    "guia" => $_POST["editarGuiaIng"],
                    "taller" => $_POST["editarTalleres"],
                    "usuario" => $_POST["idUsuario"],
                    "total" => $_POST["totalTaller"],
                    "fecha" => $fecha->format("Y-m-d")
                );
                #var_dump("datos", $datos);

                $respuesta = ModeloIngresos::mdlEditarIngreso("movimientos_cabecerajf", $datos);

                if ($respuesta == "ok") {

                    /* 
                    todo: Editamos los cambios del detalle Ingreso, primero eliminamos los detalles
                    */

                    $eliminarDato = ModeloIngresos::mdlEliminarDato("movimientosjf_2026", "documento", $_POST["editarCodigo"]);

                    $eliminarDato = "ok";

                    if ($eliminarDato == "ok") {

                        foreach ($listaArticulosOC as $key => $value) {

                            #var_dump("listaArticulosOC", $listaArticulosOC);

                            $datosD = array(
                                "tipo" => "E20",
                                "documento" => $_POST["editarCodigo"],
                                "taller" => $_POST["editarTalleres"],
                                "fecha" => $fecha->format("Y-m-d"),
                                "articulo" => $value["articulo"],
                                "cantidad" => $value["nuevaCant"],
                                "almacen" => "01",
                                "idcierre" => $value["idCierre"]
                            );
                            #var_dump("datosD", $datosD);

                            ModeloIngresos::mdlGuardarDetalleIngreso("movimientosjf_2026", $datosD);
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
                                        window.location="ingresos";}
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
									window.location="ingresos";}
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
								window.location="ingresos";}
						});
					</script>';
                }
            }
        }
    }

    static public function ctrEditarSegunda()
    {

        if (isset($_POST["idUsuario"]) && isset($_POST["listaArticulosIngreso"])) {

            #var_dump($_POST["editarCodigo"], $_POST["idUsuario"],$_POST["listaArticulosOC"]);

            if ($_POST["listaArticulosIngreso"] == "") {

                echo '<script>
						swal({
							type: "error",
							title: "Error",
							text: "¡No se cambio ninguna materia prima. Por favor, intenteló de nuevo!",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
						}).then((result)=>{
							if(result.value){
								window.location="index.php?ruta=editar-ingreso";}
						});
					</script>';
            } else {

                /* 
                todo: Traemos los datos del detalle de ingresos segunda
                */
                $detaOC = ModeloIngresos::mdlMostarDetallesIngresos("movimientosjf_2026", "documento", $_POST["editarCodigo"]);
                #var_dump("detaOC", $detaOC);

                /* 
                todo: Cabiamos los codigos de al lista por los codigos de articulos
                */
                foreach ($detaOC as $key => $value) {

                    $infoArt = controladorArticulos::ctrMostrarArticulos($value["articulo"]);
                    $detaOC[$key]["articulo"] = $infoArt["articulo"];
                    #var_dump("detaOC", $detaOC[$key]["articulo"]);

                }

                if ($_POST["listaArticulosIngreso"] == "") {

                    $listaArticulosOC = $detaOC;
                    $validarCambio = false;
                } else {

                    $listaArticulosOC = json_decode($_POST["listaArticulosIngreso"], true);
                    $validarCambio = true;
                }

                if ($validarCambio) {

                    /* 
                    todo: Actualizamos en articulos  los ingresos
                    */
                    if ($_POST["editarTipoSector"] == "0") {
                        foreach ($listaArticulosOC as $value) {

                            $tabla = "articulojf";

                            $valor = $value["articulo"];

                            //Actualizamos Taller
                            $item1 = "taller";
                            $valor1 = $value["taller"];

                            ModeloArticulos::mdlActualizarUnDato($tabla, $item1, $valor1, $valor);
                        }
                    } else {
                        foreach ($listaArticulosOC as $value) {

                            $tabla = "cierres_detallejf";

                            $valor = $value["idCierre"];
                            $articulo = $value["articulo"];

                            //Actualizar Taller
                            $item1 = "cantidad";
                            $valor1 = $value["taller"];

                            ModeloArticulos::mdlActualizarUnCierre($tabla, $item1, $valor1, $valor);

                            $valor2 = $value["cantidad"];
                            //Actualizamos servicio

                            ModeloArticulos::mdlActualizarArticuloServicio($articulo, $valor2);
                        }
                    }
                }
                $fecha = new DateTime();
                /* 
                todo: Editamos los cambios de la cabecera ingreso segunda
                */
                $datos = array(
                    "id" => $_POST["idIngreso"],
                    "documento" => $_POST["editarCodigo"],
                    "guia" => $_POST["editarGuiaIng"],
                    "taller" => $_POST["editarTalleres"],
                    "usuario" => $_POST["idUsuario"],
                    "total" => $_POST["totalTaller"],
                    "fecha" => $fecha->format("Y-m-d"),
                    "almacen" => "02",
                    "trabajador" => $_POST["editarTrabajadores"]
                );
                #var_dump("datos", $datos);

                $respuesta = ModeloIngresos::mdlEditarSegunda("movimientos_cabecerajf", $datos);

                if ($respuesta == "ok") {

                    /* 
                    todo: Editamos los cambios del detalle Ingreso Segunda, primero eliminamos los detalles
                    */

                    $eliminarDato = ModeloIngresos::mdlEliminarDato("movimientosjf_2026", "documento", $_POST["editarCodigo"]);

                    $eliminarDato = "ok";

                    if ($eliminarDato == "ok") {

                        foreach ($listaArticulosOC as $key => $value) {

                            #var_dump("listaArticulosOC", $listaArticulosOC);

                            $datosD = array(
                                "tipo" => "E20",
                                "documento" => $_POST["editarCodigo"],
                                "taller" => $_POST["editarTalleres"],
                                "fecha" => $fecha->format("Y-m-d"),
                                "articulo" => $value["articulo"],
                                "cliente" => $_POST["editarTrabajadores"],
                                "cantidad" => $value["nuevaCant"],
                                "almacen" => "02",
                                "idcierre" => $value["idCierre"]
                            );
                            #var_dump("datosD", $datosD);

                            ModeloIngresos::mdlGuardarDetalleSegunda("movimientosjf_2026", $datosD);
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
                                        window.location="ingresos";}
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
									window.location="ingresos";}
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
								window.location="ingresos";}
						});
					</script>';
                }
            }
        }
    }

    /* 
    *Método para eliminar los ingresos
    */
    static public function ctrEliminarIngreso()
    {
        if (isset($_GET["documento"]) && isset($_GET["idIngreso"])) {
            $item = "documento";
            $codigo = $_GET["documento"];

            $detaOC = ModeloIngresos::mdlMostarDetallesIngresos("movimientosjf_2026", "documento", $codigo);
            #var_dump("detaOC", $detaOC);

            $cabeceraIngreso = ModeloIngresos::mdlMostarIngresos("movimientos_cabecerajf", "id", $_GET["idIngreso"]);
            /* 
        todo: Actualizamos orden de corte en Articulojf
        */
            if ($cabeceraIngreso["taller"] == "T1" || $cabeceraIngreso["taller"] == "T3" || $cabeceraIngreso["taller"] == "T5") {
                foreach ($detaOC as $value) {

                    $tabla = "articulojf";

                    $valor = $value["articulo"];

                    //Actualizamos Taller
                    $item1 = "taller";
                    $valor1 = $value["cantidad"];

                    ModeloArticulos::mdlRecuperarTaller($valor, $valor1);

                    //Actualizamos Stock
                    $item2 = "stock";
                    $datosArt = array(
                        "articulo" => $value["articulo"],
                        "cantidad" => $value["cantidad"]
                    );

                    ModeloArticulos::mdlActualizarStock($datosArt);
                }
            } else {
                foreach ($detaOC as $value) {

                    $tabla = "cierres_detallejf";

                    $valor = $value["idcierre"];
                    $articulo = $value["articulo"];

                    //Actualizar Taller
                    $item1 = "cantidad";
                    $valor1 = $value["cantidad"];

                    ModeloArticulos::mdlRecuperarUnCierre($tabla, $item1, $valor1, $valor);

                    //Actualizamos Stock
                    $item2 = "stock";
                    $datosArt = array(
                        "articulo" => $value["articulo"],
                        "cantidad" => $value["cantidad"]
                    );

                    ModeloArticulos::mdlActualizarStock($datosArt);


                    //Actualizamos servicio
                    $valor2 = $value["cantidad"];
                    ModeloArticulos::mdlRecuperarArticuloServicio($articulo, $valor2);
                }
            }

            /* 
        todo: Eliminamos la cabecera de Ingreso
        */
            $tablaOC = "movimientos_cabecerajf";
            $itemOC = "id";
            $valorOC = $_GET["idIngreso"];

            $respuesta = ModeloIngresos::mdlEliminarDato($tablaOC, $itemOC, $valorOC);
            $respuesta = ModeloIngresos::mdlEliminarDato("movimientosjf_2026", "documento", $codigo);

            if ($respuesta == "ok") {

                /* 
            todo: Eliminamos el detalle de Orden de corte
            */
                echo '<script>
                                swal({
                                    type: "success",
                                    title: "Felicitaciones",
                                    text: "¡La información fue eliminada con éxito!",
                                    showConfirmButton: true,
                                    confirmButtonText: "Cerrar"
                                }).then((result)=>{
                                    if(result.value){
                                        window.location="ingresos";}
                                });
                            </script>';
            }

            return $respuesta;
        }
    }

    /* 
    *Método para eliminar las segundas
    */
    static public function ctrEliminarSegunda()
    {
        if (isset($_GET["documento"]) && isset($_GET["idSegunda"])) {
            $item = "documento";
            $codigo = $_GET["documento"];

            $detaOC = ModeloIngresos::mdlMostarDetallesIngresos("movimientosjf_2026", "documento", $codigo);
            #var_dump("detaOC", $detaOC);

            $cabeceraIngreso = ModeloIngresos::mdlMostarIngresos("movimientos_cabecerajf", "id", $_GET["idSegunda"]);
            /* 
        todo: Actualizamos cantidad de taller en articulojf
        */
            if ($cabeceraIngreso["taller"] == "T1" || $cabeceraIngreso["taller"] == "T3" || $cabeceraIngreso["taller"] == "T5") {
                foreach ($detaOC as $value) {

                    $tabla = "articulojf";

                    $valor = $value["articulo"];

                    //Actualizamos Taller
                    $item1 = "taller";
                    $valor1 = $value["cantidad"];

                    ModeloArticulos::mdlRecuperarTaller($valor, $valor1);
                }
            } else {
                foreach ($detaOC as $value) {

                    $tabla = "cierres_detallejf";

                    $valor = $value["idcierre"];
                    $articulo = $value["articulo"];

                    //Actualizar Taller
                    $item1 = "cantidad";
                    $valor1 = $value["cantidad"];

                    ModeloArticulos::mdlRecuperarUnCierre($tabla, $item1, $valor1, $valor);

                    $valor2 = $value["cantidad"];

                    //Actualizamos servicio

                    ModeloArticulos::mdlRecuperarArticuloServicio($articulo, $valor2);
                }
            }

            /* 
        todo: Eliminamos la cabecera de Ingreso Segunda
        */
            $tablaOC = "movimientos_cabecerajf";
            $itemOC = "id";
            $valorOC = $_GET["idSegunda"];

            $respuesta = ModeloIngresos::mdlEliminarDato($tablaOC, $itemOC, $valorOC);
            $respuesta = ModeloIngresos::mdlEliminarDato("movimientosjf_2026", "documento", $codigo);

            if ($respuesta == "ok") {

                /* 
            todo: Eliminamos el detalle de ingreso segunda
            */
                echo '<script>
                                swal({
                                    type: "success",
                                    title: "Felicitaciones",
                                    text: "¡La segunda fue eliminada con éxito!",
                                    showConfirmButton: true,
                                    confirmButtonText: "Cerrar"
                                }).then((result)=>{
                                    if(result.value){
                                        window.location="ingresos";}
                                });
                            </script>';
            }

            return $respuesta;
        }
    }


    /*=============================================
	RANGO FECHAS
	=============================================*/

    static public function ctrRangoFechasIngresos($fechaInicial, $fechaFinal)
    {

        $tabla = "movimientos_cabecerajf";

        $respuesta = ModeloIngresos::mdlRangoFechasIngresos($tabla, $fechaInicial, $fechaFinal);

        return $respuesta;
    }

    /*=============================================
	RANGO FECHAS PARA VISUALIZAR INGRESOS
	=============================================*/

    static public function ctrRangoFechasVerIngresos($fechaInicial, $fechaFinal)
    {

        $tabla = "movimientos_cabecerajf";

        $respuesta = ModeloIngresos::mdlRangoFechasVerIngresos($tabla, $fechaInicial, $fechaFinal);

        return $respuesta;
    }

    /* 
    * Actualizar FECHA
    */
    static public function ctrActualizarFecha()
    {

        if (isset($_POST["cierre"])) {

            $tipo = $_POST["tipoIng"];
            $documento = $_POST["cierre"];
            $fecha = $_POST["fecha"];

            $datos = array(
                "tipo" => $tipo,
                "documento" => $documento,
                "fecha" => $fecha
            );

            #var_dump($datos);

            $respuesta = ModeloIngresos::mdlActualizarFecha($datos);
            #var_dump($respuesta);

            if ($respuesta == "ok") {

                echo '<script>
                swal({
                    type: "success",
                    title: "Felicitaciones",
                    text: "¡La información fue Actualizada con éxito!",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                }).then((result)=>{
                    if(result.value){
                        window.location="ingresos";}
                });
            </script>';
            }
        }
    }

    //* Editar Ingreso
    static public function ctrEditarIngresoB()
    {

        if (isset($_POST["codigo"])) {

            //*Datos
            $codigo     = $_POST["codigo"];
            $articulo   = $_POST["articulo"];
            $cantidadO  = $_POST["cantidadO"];
            $cantidad   = $_POST["cantidad"];
            $saldo      = $_POST["saldo"];
            $sector     = $_POST["sector"];
            $idcierre   = $_POST["idcierre"];
            $almacen   = $_POST["almacen"];
            $usureg     = $_SESSION["nombre"];
            $pcreg      = gethostbyaddr($_SERVER['REMOTE_ADDR']);

            $stock = ($cantidadO * -1) + $cantidad;
            $prod = $cantidadO - $cantidad;

            // if ($sector = "externo") {
            //     $actCierre = ModeloIngresos::mdlactualizarCierre($idcierre, $saldo);
            // }

            $actStock = ModeloIngresos::mdlactualizarStock($sector, $articulo, $stock, $prod, $almacen);

            $actMov = ModeloIngresos::mdlactualizarMovimiento($codigo, $articulo, $cantidadO, $cantidad);

            if ($actMov == "ok") {

                echo '<script>

                swal({
                    type: "success",
                    title: "El movimiento se edito correctamente",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                    }).then(function(result){
                                if (result.value) {

                                window.location = "ingresos";

                                }
                            })

                </script>';
            }
        }
    }

    //* Agregar a Taller
    static public function ctrAgregarTaller()
    {

        if (isset($_POST["articulo"])) {

            date_default_timezone_set('America/Lima');
            $fecreg             = new DateTime();
            $pcreg                = gethostbyaddr($_SERVER['REMOTE_ADDR']);
            $usureg                = $_SESSION["nombre"];

            $correlativo = ControladorSalidas::ctrMostrarArgumentoSalida("E49");

            $numero = str_pad($correlativo["argumento"], 5, '0', STR_PAD_LEFT);

            $documento = "E49" . $numero;

            $datosD = array(
                "tipo"      => "E49",
                "documento" => $documento,
                "taller"    => "T3",
                "fecha"     => $fecreg->format("Y-m-d"),
                "cliente"   => $_POST["usuario"],
                "articulo"  => $_POST["articulo"],
                "cantidad"  => $_POST["cantidad"],
                "almacen"   => "02",
                "idcierre"  => 0
            );

            // $ingreso = ModeloIngresos::mdlGuardarDetalleSegunda("movimientosjf_2026", $datosD);

            $agregar = ModeloArticulos::mdlActualizarTallerIngreso($_POST["articulo"], $_POST["cantidad"]);
            if ($agregar == "ok") {

                echo '<script>

                    swal({
                        type: "success",
                        title: "Se registro correctamente",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar"
                        }).then(function(result) {
                                    if (result.value) {

                                    window.location = "ajuste-taller";

                                    }
                                })

                </script>';
            }
        }
    }

    static public function ctrMostraAjustes()
    {

        $respuesta = ModeloIngresos::mdlMostraAjustes();

        return $respuesta;
    }


    static public function ctrCrearIngresoC()
    {

        if (isset($_POST["nuevoTalleres"]) && isset($_POST["idUsuario"])) {


            if ($_POST["listaArticulosIngreso"] == "") {
                self::mostrarAlertaError("¡No se seleccionó ningún artículo. Por favor, intenteló de nuevo!", "crear-ingresos");
            } else {
                $listaArticulos = json_decode($_POST["listaArticulosIngreso"], true);
                $entaller = self::ActualizarEnTaller($listaArticulos);

                foreach ($entaller as $id => $cantidad) {
                    $idEntrada = $id;
                    $cantidadRestante = $cantidad;

                    ModeloIngresos::mdlActualizarSaldoEnTaller($idEntrada, $cantidadRestante);
                }

                if ($_POST["nuevoTipoSector"] == "0") {
                    self::actualizarArticulos($listaArticulos);
                } else {
                    self::actualizarCierresDetalle($listaArticulos);
                }

                $respuesta = self::guardarIngreso($_POST);

                if ($respuesta == "ok") {
                    self::guardarDetallesIngreso($listaArticulos, $_POST);
                    ModeloCierres::mdlActualizarServicioTotal();
                    self::mostrarAlertaExito("¡La información fue registrada con éxito!", "ingresos");
                } else {
                    self::mostrarAlertaError("¡La información presento problemas y no se registro adecuadamente. Por favor, intenteló de nuevo!", "crear-ingresos");
                }
            }
        }
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

            // Actualizamos Taller
            $item1 = "taller";
            $valor1 = $value["taller"] < 0 ? 0 : $value["taller"];

            ModeloArticulos::mdlActualizarUnDato($tabla, $item1, $valor1, $valor);

            // Actualizamos Stock
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

            // Actualizar Taller
            $item1 = "cantidad";
            $valor1 = $value["taller"] < 0 ? 0 : $value["taller"];

            ModeloArticulos::mdlActualizarUnCierre($tabla, $item1, $valor1, $valor);

            // Actualizamos Stock
            $item2 = "stock";
            $valor2 = $value["cantidad"];

            ModeloArticulos::mdlActualizarStockIngreso($articulo, $valor2);
            ModeloArticulos::mdlActualizarStockIngreso01($articulo, $valor2);

            // Actualizamos servicio
            ModeloArticulos::mdlActualizarArticuloServicio($articulo, $valor2);
        }
    }

    private function guardarIngreso($postData)
    {
        $fecha = $postData["nuevaFecha"];
        $datos = array(
            "tipo" => "E20",
            "usuario" => $postData["idUsuario"],
            "guia" => $postData["nuevaGuiaIng"],
            "taller" => $postData["nuevoTalleres"],
            "documento" => $postData["nuevoCodigo"],
            "total" => $postData["totalTaller"],
            "fecha" => $fecha,
            "almacen" => "01"
        );

        return ModeloIngresos::mdlGuardarIngreso("movimientos_cabecerajf", $datos);
    }

    private function guardarDetallesIngreso($listaArticulos, $postData)
    {
        $fecha = $postData["nuevaFecha"];

        $taller = substr($postData["nuevoTalleres"], 1);
        $fechaEncode = self::convertirFechaExcel($fecha);

        foreach ($listaArticulos as $key => $value) {
            $datosD = array(
                "tipo" => "E20",
                "documento" => $postData["nuevoCodigo"],
                "taller" => $postData["nuevoTalleres"],
                "fecha" => $fecha,
                "articulo" => $value["articulo"],
                "cantidad" => $value["cantidad"],
                "almacen" => "01",
                "idcierre" => $value["idCierre"],
                "corte" => strtoupper($taller . $value["corte"]) . $fechaEncode
            );

            ModeloIngresos::mdlGuardarDetalleIngreso("movimientosjf_2026", $datosD);
        }
    }

    public static function ActualizarEnTaller($listaArticulos)
    {
        // Array para almacenar las cantidades pendientes por cada entrada en el taller
        $resultado = [];

        foreach ($listaArticulos as $item) {
            $articulo = $item["articulo"];
            $cantidadConsumir = $item["cantidad"];

            // Obtener todas las entradas en el taller para el artículo actual
            $enTaller = ModeloIngresos::buscarEnTaller($articulo);

            // Si no hay entradas en el taller, continuar con el siguiente artículo
            if (empty($enTaller)) {
                continue;
            }

            // Variable para acumular la cantidad restante a consumir
            $restante = $cantidadConsumir;

            foreach ($enTaller as &$entrada) {
                if ($restante <= 0) {
                    // No se necesita consumir más de esta entrada
                    $resultado[$entrada["id"]] = $entrada["saldo"];
                    continue;
                }

                // Obtener la cantidad pendiente en esta entrada del taller
                $saldoDisponible = $entrada["saldo"];

                if ($saldoDisponible >= $restante) {
                    // Si el saldo disponible es suficiente para cubrir el restante
                    $entrada["saldo"] -= $restante;
                    $resultado[$entrada["id"]] = $entrada["saldo"];
                    $restante = 0;
                } else {
                    // Si no es suficiente, restar todo lo disponible y continuar
                    $restante -= $saldoDisponible;
                    $entrada["saldo"] = 0;
                    $resultado[$entrada["id"]] = 0;
                }
            }
            unset($entrada); // Romper la referencia
        }

        return $resultado;
    }

    static public function convertirFechaExcel($fecha)
    {
        // Intenta convertir usando el formato Y-m-d primero
        $fechaObjeto = DateTime::createFromFormat('Y-m-d', $fecha);

        // Si falla, intenta con el formato d/m/Y
        if (!$fechaObjeto) {
            $fechaObjeto = DateTime::createFromFormat('d/m/Y', $fecha);
        }

        // Si aún falla, maneja el error adecuadamente
        if (!$fechaObjeto) {
            throw new Exception("Formato de fecha inválido: " . $fecha);
        }

        // Fecha base de Excel (1 de enero de 1900)
        $fechaBase = DateTime::createFromFormat('d/m/Y', '01/01/1900');

        // Calcular la diferencia en días
        $diferencia = $fechaBase->diff($fechaObjeto)->days;

        // Excel cuenta el día 29 de febrero de 1900, que técnicamente no existió
        // Como estamos iniciando desde el 0, debemos ajustar agregando 2 en lugar de 1
        if ($fechaObjeto > DateTime::createFromFormat('d/m/Y', '28/02/1900')) {
            $diferencia += 2;
        }

        return $diferencia;
    }
}
