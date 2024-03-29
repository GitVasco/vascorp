<?php

class ControladorIngresos{

    /* 
    * MOSTRAR DATOS DE INGRESO CABECERA
    */
    static public function ctrMostrarIngresos($item, $valor){

        $tabla = "movimientos_cabecerajf";

        $respuesta = ModeloIngresos::mdlMostarIngresos($tabla, $item, $valor);

        return $respuesta;

    }

    /* 
    * MOSTRAR DETALLE DE INGRESO
    */
    static public function ctrMostrarDetallesIngresos($item, $valor){

        $tabla = "movimientosjf_2021";

        $respuesta = ModeloIngresos::mdlMostarDetallesIngresos($tabla, $item, $valor);

        return $respuesta;

    }

    /* 
    * MOSTRAR DATOS DE LAS ARTICULOS O CIERRES PARA CREAR INGRESO
    */
    static public function ctrMostrarArticulosCierres($idCierre){

        $respuesta = ModeloArticulos::mdlMostrarArticulosCierres( $idCierre);

        return $respuesta;

    }

    // VISUALIZAR INGRESO DETALLE
	static public function ctrVisualizarIngresoDetalle($valor){

        $respuesta = ModeloIngresos::mdlVisualizarIngresoDetalle($valor);
        
		return $respuesta;

	} 

    /* 
    * CREAR INGRESO
    */
    static public function ctrCrearIngreso(){

        /* 
        todo: Verificamos que traiga datos
        */
        if( isset($_POST["nuevoTalleres"]) &&
            isset($_POST["idUsuario"])){

                #var_dump("nuevaOrdenCorte", $_POST["nuevaOrdenCorte"]);

                if($_POST["listaArticulosIngreso"] == ""){

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

                }else{

                    /* 
                    ? Actualizamos la cantidad de la orden de corte
                    */

                    $listaArticulos = json_decode($_POST["listaArticulosIngreso"], true);

                    #var_dump("listaArticulos", $listaArticulos);

                    if($_POST["nuevoTipoSector"] == "0"){
                        foreach($listaArticulos as $value){

                            $tabla = "articulojf";
    
                            $valor = $value["articulo"];
                            
                            //Actualizamos Taller
                            $item1 = "taller";
                            $valor1 = $value["taller"];

                            ModeloArticulos::mdlActualizarUnDato($tabla, $item1, $valor1, $valor);

                            //Actualizamos Stock
                            $item2="stock";
                            $valor2= $value["cantidad"];
    
                            ModeloArticulos::mdlActualizarStockIngreso( $valor, $valor2);
    
                        }
                    }else{
                        foreach($listaArticulos as $value){

                            $tabla = "cierres_detallejf";
    
                            $valor = $value["idCierre"];
                            $articulo= $value["articulo"];
                            
                            //Actualizar Taller
                            $item1 = "cantidad";
                            $valor1 = $value["taller"];
    
                            ModeloArticulos::mdlActualizarUnCierre($tabla, $item1, $valor1, $valor);

                            //Actualizamos Stock
                            $item2="stock";
                            $valor2= $value["cantidad"];
    
                            ModeloArticulos::mdlActualizarStockIngreso( $articulo, $valor2);

                            //Actualizamos servicio
                            
                            ModeloArticulos::mdlActualizarArticuloServicio( $articulo, $valor2);
                        }
                    }

                    

                    /* 
                    * GUARDAR EL INGRESO
                    */
                    $fecha=new DateTime();
                    $datos = array( "tipo"=>"E20",
                                    "usuario"=>$_POST["idUsuario"],
                                    "guia"=>$_POST["nuevaGuiaIng"],
                                    "taller"=>$_POST["nuevoTalleres"],
                                    "documento"=>$_POST["nuevoCodigo"],
                                    "total"=>$_POST["totalTaller"],
                                    "fecha"=>$fecha->format("Y-m-d"),
                                    "almacen" => "01");

                    #var_dump("datos", $datos);
                    
                    $respuesta = ModeloIngresos::mdlGuardarIngreso("movimientos_cabecerajf", $datos);

                    if($respuesta == "ok"){


                        foreach($listaArticulos as $key=>$value){

                            $datosD = array("tipo"=>"E20",
                                            "documento"=>$_POST["nuevoCodigo"],
                                            "taller"=>$_POST["nuevoTalleres"],
                                            "fecha"=>$fecha->format("Y-m-d"),
                                            "articulo"=>$value["articulo"],
                                            "cantidad"=>$value["cantidad"],
                                            "almacen"=>"01",
                                            "idcierre"=>$value["idCierre"]);

                            #var_dump("datosD", $datosD);

                            ModeloIngresos::mdlGuardarDetalleIngreso("movimientosjf_2021", $datosD);

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

                    }else{

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
    static public function ctrCrearSegunda(){

        /* 
        todo: Verificamos que traiga datos
        */
        if( isset($_POST["nuevoTalleres"]) &&
            isset($_POST["idUsuario"])){

                #var_dump("nuevaOrdenCorte", $_POST["nuevaOrdenCorte"]);

                if($_POST["listaArticulosIngreso"] == ""){

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

                }else{

                    /* 
                    ? Actualizamos la cantidad de taller en articulos
                    */

                    $listaArticulos = json_decode($_POST["listaArticulosIngreso"], true);

                    #var_dump("listaArticulos", $listaArticulos);

                    if($_POST["nuevoTipoSector"] == "0"){
                        foreach($listaArticulos as $value){

                            $tabla = "articulojf";
    
                            $valor = $value["articulo"];
                            
                            //Actualizamos Taller
                            $item1 = "taller";
                            $valor1 = $value["taller"];

                            ModeloArticulos::mdlActualizarUnDato($tabla, $item1, $valor1, $valor);

                            
    
                        }
                    }else{
                        foreach($listaArticulos as $value){

                            $tabla = "cierres_detallejf";
    
                            $valor = $value["idCierre"];
                            $articulo= $value["articulo"];
                            
                            //Actualizar Taller
                            $item1 = "cantidad";
                            $valor1 = $value["taller"];
    
                            ModeloArticulos::mdlActualizarUnCierre($tabla, $item1, $valor1, $valor);

                           
                            $valor2= $value["cantidad"];
                            //Actualizamos servicio
                            
                            ModeloArticulos::mdlActualizarArticuloServicio( $articulo, $valor2);
                        }
                    }
                    /* 
                    * GUARDAR EL INGRESO
                    */
                    $fecha=new DateTime();
                    $datos = array( "tipo"=>"E20",
                                    "usuario"=>$_POST["idUsuario"],
                                    "guia"=>$_POST["nuevaGuiaIng"],
                                    "taller"=>$_POST["nuevoTalleres"],
                                    "documento"=>$_POST["nuevoCodigo"],
                                    "total"=>$_POST["totalTaller"],
                                    "fecha"=>$fecha->format("Y-m-d"),
                                    "almacen" => "02",
                                    "trabajador"=>$_POST["nuevoTrabajadores"]);

                    #var_dump("datos", $datos);
                    
                    $respuesta = ModeloIngresos::mdlGuardarSegunda("movimientos_cabecerajf", $datos);

                    if($respuesta == "ok"){


                        foreach($listaArticulos as $key=>$value){

                            $datosD = array("tipo"=>"E20",
                                            "documento"=>$_POST["nuevoCodigo"],
                                            "taller"=>$_POST["nuevoTalleres"],
                                            "fecha"=>$fecha->format("Y-m-d"),
                                            "articulo"=>$value["articulo"],
                                            "cliente"=>$_POST["nuevoTrabajadores"],
                                            "cantidad"=>$value["cantidad"],
                                            "almacen"=>"02",
                                            "idcierre"=>$value["idCierre"]);

                            #var_dump("datosD", $datosD);

                            ModeloIngresos::mdlGuardarDetalleSegunda("movimientosjf_2021", $datosD);

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

                    }else{

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
    static public function ctrEditarIngreso(){

        if(isset($_POST["idUsuario"]) && isset($_POST["listaArticulosIngreso"])){

            #var_dump($_POST["editarCodigo"], $_POST["idUsuario"],$_POST["listaArticulosOC"]);

            if($_POST["listaArticulosIngreso"] == ""){

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

            }else{

                /* 
                todo: Traemos los datos del detalle de ingreso
                */
                $detaOC = ModeloIngresos::mdlMostarDetallesIngresos("movimientosjf_2021", "documento", $_POST["editarCodigo"]);
                #var_dump("detaOC", $detaOC);

                /* 
                todo: Cabiamos los codigos de al lista por los codigos de articulos
                */
                foreach($detaOC as $key=>$value){

                    $infoArt = controladorArticulos::ctrMostrarArticulos($value["articulo"]);
                    $detaOC[$key]["articulo"]=$infoArt["articulo"];
                    #var_dump("detaOC", $detaOC[$key]["articulo"]);

                }

                if($_POST["listaArticulosIngreso"] == ""){

                    $listaArticulosOC = $detaOC;
                    $validarCambio = false;

                }else{

                    $listaArticulosOC = json_decode($_POST["listaArticulosIngreso"], true);
                    $validarCambio = true;

                }

                if($validarCambio){

                    /* 
                    todo: Actualizamos en articulos de ingresos
                    */
                    if($_POST["editarTipoSector"] == "0"){
                        foreach($listaArticulosOC as $value){

                            $tabla = "articulojf";
    
                            $valor = $value["articulo"];
                            
                            //Actualizamos Taller
                            $item1 = "taller";
                            $valor1 = $value["taller"];

                            ModeloArticulos::mdlActualizarUnDato($tabla, $item1, $valor1, $valor);

                            //Actualizamos Stock
                            $item2="stock";
                            $valor2= $value["cantidad"];
    
                            ModeloArticulos::mdlActualizarStockIngreso( $valor, $valor2);
    
                        }
                    }else{
                        foreach($listaArticulosOC as $value){

                            $tabla = "cierres_detallejf";
    
                            $valor = $value["idCierre"];
                            $articulo= $value["articulo"];
                            
                            //Actualizar Taller
                            $item1 = "cantidad";
                            
                            $valor1 = $value["taller"];
    
                            ModeloArticulos::mdlActualizarUnCierre($tabla, $item1, $valor1, $valor);

                            //Actualizamos Stock
                            $item2="stock";
                            $valor2= $value["cantidad"];
    
                            ModeloArticulos::mdlActualizarStockIngreso( $articulo, $valor2);

                            //Actualizamos servicio
                            
                            ModeloArticulos::mdlActualizarArticuloServicio( $articulo, $valor2);
                        }
                    }

                }
                $fecha=new DateTime();
                /* 
                todo: Editamos los cambios de la cabecera ingreso
                */
                $datos = array( "id" => $_POST["idIngreso"],
                                "documento"=>$_POST["editarCodigo"],
                                "guia"=>$_POST["editarGuiaIng"],
                                "taller"=>$_POST["editarTalleres"],
                                "usuario"=>$_POST["idUsuario"],
                                "total"=>$_POST["totalTaller"],
                                "fecha"=>$fecha->format("Y-m-d"));
                #var_dump("datos", $datos);

                $respuesta = ModeloIngresos::mdlEditarIngreso("movimientos_cabecerajf", $datos);

                if($respuesta == "ok"){

                    /* 
                    todo: Editamos los cambios del detalle Ingreso, primero eliminamos los detalles
                    */

                    $eliminarDato = ModeloIngresos::mdlEliminarDato("movimientosjf_2021", "documento", $_POST["editarCodigo"]);

                    $eliminarDato = "ok";

                    if($eliminarDato == "ok"){

                        foreach($listaArticulosOC as $key=>$value){

                            #var_dump("listaArticulosOC", $listaArticulosOC);

                            $datosD = array("tipo"=>"E20",
                                            "documento"=>$_POST["editarCodigo"],
                                            "taller"=>$_POST["editarTalleres"],
                                            "fecha"=>$fecha->format("Y-m-d"),
                                            "articulo"=>$value["articulo"],
                                            "cantidad"=>$value["nuevaCant"],
                                            "almacen" => "01",
                                            "idcierre"=>$value["idCierre"]);
                            #var_dump("datosD", $datosD);

                            ModeloIngresos::mdlGuardarDetalleIngreso("movimientosjf_2021", $datosD);

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


                    }else{

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


                }else{

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

    static public function ctrEditarSegunda(){

        if(isset($_POST["idUsuario"]) && isset($_POST["listaArticulosIngreso"])){

            #var_dump($_POST["editarCodigo"], $_POST["idUsuario"],$_POST["listaArticulosOC"]);

            if($_POST["listaArticulosIngreso"] == ""){

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

            }else{

                /* 
                todo: Traemos los datos del detalle de ingresos segunda
                */
                $detaOC = ModeloIngresos::mdlMostarDetallesIngresos("movimientosjf_2021", "documento", $_POST["editarCodigo"]);
                #var_dump("detaOC", $detaOC);

                /* 
                todo: Cabiamos los codigos de al lista por los codigos de articulos
                */
                foreach($detaOC as $key=>$value){

                    $infoArt = controladorArticulos::ctrMostrarArticulos($value["articulo"]);
                    $detaOC[$key]["articulo"]=$infoArt["articulo"];
                    #var_dump("detaOC", $detaOC[$key]["articulo"]);

                }

                if($_POST["listaArticulosIngreso"] == ""){

                    $listaArticulosOC = $detaOC;
                    $validarCambio = false;

                }else{

                    $listaArticulosOC = json_decode($_POST["listaArticulosIngreso"], true);
                    $validarCambio = true;

                }

                if($validarCambio){

                    /* 
                    todo: Actualizamos en articulos  los ingresos
                    */
                    if($_POST["editarTipoSector"] == "0"){
                        foreach($listaArticulosOC as $value){

                            $tabla = "articulojf";
    
                            $valor = $value["articulo"];
                            
                            //Actualizamos Taller
                            $item1 = "taller";
                            $valor1 = $value["taller"];

                            ModeloArticulos::mdlActualizarUnDato($tabla, $item1, $valor1, $valor);

                        }
                    }else{
                        foreach($listaArticulosOC as $value){

                            $tabla = "cierres_detallejf";
    
                            $valor = $value["idCierre"];
                            $articulo= $value["articulo"];
                            
                            //Actualizar Taller
                            $item1 = "cantidad";
                            $valor1 = $value["taller"];
    
                            ModeloArticulos::mdlActualizarUnCierre($tabla, $item1, $valor1, $valor);
                            
                            $valor2= $value["cantidad"];
                            //Actualizamos servicio
                            
                            ModeloArticulos::mdlActualizarArticuloServicio( $articulo, $valor2);
                        }
                    }

                }
                $fecha=new DateTime();
                /* 
                todo: Editamos los cambios de la cabecera ingreso segunda
                */
                $datos = array( "id" => $_POST["idIngreso"],
                                "documento"=>$_POST["editarCodigo"],
                                "guia"=>$_POST["editarGuiaIng"],
                                "taller"=>$_POST["editarTalleres"],
                                "usuario"=>$_POST["idUsuario"],
                                "total"=>$_POST["totalTaller"],
                                "fecha"=>$fecha->format("Y-m-d"),
                                "almacen"=>"02",
                                "trabajador" => $_POST["editarTrabajadores"]);
                #var_dump("datos", $datos);

                $respuesta = ModeloIngresos::mdlEditarSegunda("movimientos_cabecerajf", $datos);

                if($respuesta == "ok"){

                    /* 
                    todo: Editamos los cambios del detalle Ingreso Segunda, primero eliminamos los detalles
                    */

                    $eliminarDato = ModeloIngresos::mdlEliminarDato("movimientosjf_2021", "documento", $_POST["editarCodigo"]);

                    $eliminarDato = "ok";

                    if($eliminarDato == "ok"){

                        foreach($listaArticulosOC as $key=>$value){

                            #var_dump("listaArticulosOC", $listaArticulosOC);

                            $datosD = array("tipo"=>"E20",
                                            "documento"=>$_POST["editarCodigo"],
                                            "taller"=>$_POST["editarTalleres"],
                                            "fecha"=>$fecha->format("Y-m-d"),
                                            "articulo"=>$value["articulo"],
                                            "cliente"=>$_POST["editarTrabajadores"],
                                            "cantidad"=>$value["nuevaCant"],
                                            "almacen" => "02",
                                            "idcierre"=>$value["idCierre"]);
                            #var_dump("datosD", $datosD);

                            ModeloIngresos::mdlGuardarDetalleSegunda("movimientosjf_2021", $datosD);

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


                    }else{

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


                }else{

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
    static public function ctrEliminarIngreso(){
        if(isset($_GET["documento"]) && isset($_GET["idIngreso"]) ){
        $item = "documento";
        $codigo=$_GET["documento"];

        $detaOC = ModeloIngresos::mdlMostarDetallesIngresos("movimientosjf_2021", "documento", $codigo);
        #var_dump("detaOC", $detaOC);
         
        $cabeceraIngreso= ModeloIngresos::mdlMostarIngresos("movimientos_cabecerajf","id",$_GET["idIngreso"]);
        /* 
        todo: Actualizamos orden de corte en Articulojf
        */
        if($cabeceraIngreso["taller"] == "T1" || $cabeceraIngreso["taller"] == "T3" || $cabeceraIngreso["taller"] == "T5"){
            foreach($detaOC as $value){

                $tabla = "articulojf";

                $valor = $value["articulo"];
                
                //Actualizamos Taller
                $item1 = "taller";
                $valor1 = $value["cantidad"];

                ModeloArticulos::mdlRecuperarTaller( $valor, $valor1);

                //Actualizamos Stock
                $item2="stock";
                $datosArt = array( "articulo" => $value["articulo"],
                                    "cantidad" => $value["cantidad"]);

                ModeloArticulos::mdlActualizarStock( $datosArt);

                

            }
        }else{
            foreach($detaOC as $value){

                $tabla = "cierres_detallejf";

                $valor = $value["idcierre"];
                $articulo= $value["articulo"];
                
                //Actualizar Taller
                $item1 = "cantidad";
                $valor1 = $value["cantidad"];

                ModeloArticulos::mdlRecuperarUnCierre($tabla, $item1, $valor1, $valor);

                //Actualizamos Stock
                $item2="stock";
                $datosArt = array( "articulo" => $value["articulo"],
                                    "cantidad" => $value["cantidad"]);

                ModeloArticulos::mdlActualizarStock( $datosArt);


                //Actualizamos servicio
                $valor2=$value["cantidad"];
                ModeloArticulos::mdlRecuperarArticuloServicio( $articulo, $valor2);
            }
        }

        /* 
        todo: Eliminamos la cabecera de Ingreso
        */
        $tablaOC = "movimientos_cabecerajf";
        $itemOC = "id";
        $valorOC = $_GET["idIngreso"];

        $respuesta = ModeloIngresos::mdlEliminarDato($tablaOC, $itemOC, $valorOC);
        $respuesta= ModeloIngresos::mdlEliminarDato("movimientosjf_2021","documento",$codigo);

        if($respuesta == "ok"){

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
    static public function ctrEliminarSegunda(){
        if(isset($_GET["documento"]) && isset($_GET["idSegunda"]) ){
        $item = "documento";
        $codigo=$_GET["documento"];

        $detaOC = ModeloIngresos::mdlMostarDetallesIngresos("movimientosjf_2021", "documento", $codigo);
        #var_dump("detaOC", $detaOC);
         
        $cabeceraIngreso= ModeloIngresos::mdlMostarIngresos("movimientos_cabecerajf","id",$_GET["idSegunda"]);
        /* 
        todo: Actualizamos cantidad de taller en articulojf
        */
        if($cabeceraIngreso["taller"] == "T1" || $cabeceraIngreso["taller"] == "T3" || $cabeceraIngreso["taller"] == "T5"){
            foreach($detaOC as $value){

                $tabla = "articulojf";

                $valor = $value["articulo"];
                
                //Actualizamos Taller
                $item1 = "taller";
                $valor1 = $value["cantidad"];

                ModeloArticulos::mdlRecuperarTaller( $valor, $valor1);

            }
        }else{
            foreach($detaOC as $value){

                $tabla = "cierres_detallejf";

                $valor = $value["idcierre"];
                $articulo= $value["articulo"];
                
                //Actualizar Taller
                $item1 = "cantidad";
                $valor1 = $value["cantidad"];

                ModeloArticulos::mdlRecuperarUnCierre($tabla, $item1, $valor1, $valor);

                $valor2= $value["cantidad"];

                //Actualizamos servicio
                
                ModeloArticulos::mdlRecuperarArticuloServicio( $articulo, $valor2);
            }
        }

        /* 
        todo: Eliminamos la cabecera de Ingreso Segunda
        */
        $tablaOC = "movimientos_cabecerajf";
        $itemOC = "id";
        $valorOC = $_GET["idSegunda"];

        $respuesta = ModeloIngresos::mdlEliminarDato($tablaOC, $itemOC, $valorOC);
        $respuesta= ModeloIngresos::mdlEliminarDato("movimientosjf_2021","documento",$codigo);

        if($respuesta == "ok"){

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

	static public function ctrRangoFechasIngresos($fechaInicial, $fechaFinal){

		$tabla = "movimientos_cabecerajf";

		$respuesta = ModeloIngresos::mdlRangoFechasIngresos($tabla, $fechaInicial, $fechaFinal);

		return $respuesta;
		
    }

    /*=============================================
	RANGO FECHAS PARA VISUALIZAR INGRESOS
	=============================================*/	
   
    static public function ctrRangoFechasVerIngresos($fechaInicial, $fechaFinal){

		$tabla = "movimientos_cabecerajf";

		$respuesta = ModeloIngresos::mdlRangoFechasVerIngresos($tabla, $fechaInicial, $fechaFinal);

		return $respuesta;
		
    }
}