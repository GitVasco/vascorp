<?php

class ControladorCentroCostos
{

    /*
	*MOSTRAR AREAS
	*/
    static public function ctrMostrarAreas($valor)
    {

        $respuesta = ModeloCentroCostos::mdlMostrarAreas($valor);

        return $respuesta;
    }

    /* 
    *Mostrar centro de costos
    */
    static public function ctrMostrarCentroCostos($valor)
    {

        $respuesta = ModeloCentroCostos::mdlMostrarCentroCostos($valor);

        return $respuesta;
    }

    /* 
    *Mostrar centro de costos
    */
    static public function ctrMostrarCentroCostosResumen($valor)
    {

        $respuesta = ModeloCentroCostos::mdlMostrarCentroCostosResumen($valor);

        return $respuesta;
    }

    /* 
    *Mostrar centro de costos
    */
    static public function ctrMostrarCorrelativoRecibo()
    {

        $respuesta = ModeloCentroCostos::mdlMostrarCorrelativoRecibo();

        return $respuesta;
    }

    /* 
    *Mostrar Correlativo
    */
    static public function ctrMostrarCorrelativo($tipoGasto, $area)
    {

        $respuesta = ModeloCentroCostos::mdlMostrarCorrelativo($tipoGasto, $area);

        return $respuesta;
    }

    /* 
    *CREAR CENTRO DE COSTOS
    */
    static public function ctrCrearCentroCostos()
    {

        if (isset($_POST["nuevoCod"])) {

            # traemos la fecha y la pc
            date_default_timezone_set('America/Lima');
            $fecha = new DateTime();
            $PcReg = gethostbyaddr($_SERVER['REMOTE_ADDR']);

            $codigo = substr($_POST["nuevoCod"], -2);
            #var_dump($codigo);
            if ($codigo == "01") {

                $datos = array(
                    "tipo_gasto"    =>  $_POST["tipoGasto"],
                    "cod_area"      =>  $_POST["Area"],
                    "cod_caja"      =>  $_POST["nuevoCod"],
                    "descripcion"   =>  $_POST["nuevoCC"],
                    "fecmod"        =>  $fecha->format("Y-m-d H:i:s"),
                    "usumod"        =>  $_SESSION["nombre"],
                    "pcmod"         =>  $PcReg
                );
                #var_dump($datos);

                $respuesta = ModeloCentroCostos::mdlActualizarACentroCostos($datos);
                #var_dump($respuesta);

                if ($respuesta == "ok") {

                    ModeloCentroCostos::mdlPonerNombres();

                    # Mostramos una alerta suave
                    echo '<script>
                            swal({
                                type: "success",
                                title: "Felicitaciones",
                                text: "¡El centro de costos fue registrado con éxito!",
                                showConfirmButton: true,
                                confirmButtonText: "Cerrar"
                            }).then((result)=>{
                                if(result.value){
                                    window.location="centro-costos";}
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
                                    window.location="centro-costos";}
                            });
                        </script>';
                }
            } else {

                $datos = array(
                    "tipo_gasto"    =>  $_POST["tipoGasto"],
                    "cod_area"      =>  $_POST["Area"],
                    "cod_caja"      =>  $_POST["nuevoCod"],
                    "descripcion"   =>  $_POST["nuevoCC"],
                    "fecreg"        =>  $fecha->format("Y-m-d H:i:s"),
                    "usureg"        =>  $_SESSION["nombre"],
                    "pcreg"         =>  $PcReg
                );
                #var_dump($datos);

                $respuesta = ModeloCentroCostos::mdlCrearCentroCostos($datos);
                #var_dump($respuesta);

                if ($respuesta == "ok") {

                    ModeloCentroCostos::mdlPonerNombres();

                    # Mostramos una alerta suave
                    echo '<script>
                            swal({
                                type: "success",
                                title: "Felicitaciones",
                                text: "¡El centro de costos fue registrado con éxito!",
                                showConfirmButton: true,
                                confirmButtonText: "Cerrar"
                            }).then((result)=>{
                                if(result.value){
                                    window.location="centro-costos";}
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
                                    window.location="centro-costos";}
                            });
                        </script>';
                }
            }
        }
    }

    /*
	*MOSTRAR GASTOS DE CAJA
	*/
    static public function ctrMostrarGastosCaja($mes)
    {

        $respuesta = ModeloCentroCostos::mdlMostrarGastosCaja($mes);

        return $respuesta;
    }

    /*
	*MOSTRAR GASTOS DE CAJA
	*/
    static public function ctrMostrarGastosCajaUsuario($usuario)
    {

        $respuesta = ModeloCentroCostos::mdlMostrarGastosCajaUsuario($usuario);

        return $respuesta;
    }

    /*
	*MOSTRAR GASTOS DE CAJA
	*/
    static public function ctrMostrarGastosCajaId($id)
    {

        $respuesta = ModeloCentroCostos::mdlMostrarGastosCajaId($id);

        return $respuesta;
    }

    /*
	*MOSTRAR GASTOS DE CAJA
	*/
    static public function ctrMostrarIngresosCajaId($id)
    {

        $respuesta = ModeloCentroCostos::mdlMostrarIngresosCajaId($id);

        return $respuesta;
    }

    /* 
    *Mostrar centro de costos con codigo de caja
    */
    static public function ctrMostrarCentroCostosCaja()
    {

        $respuesta = ModeloCentroCostos::mdlMostrarCentroCostosCaja();

        return $respuesta;
    }

    /* 
    *Mostrar Correlativo
    */
    static public function ctrMostrarTipoDoc()
    {

        $respuesta = ModeloCentroCostos::mdlMostrarTipoDoc();

        return $respuesta;
    }

    /* 
    *CREAR GASTOS
    */
    static public function ctrCrearGastosCaja()
    {

        if (isset($_POST["nuevoCodCaja"])) {

            $fechaGasto = $_POST["fechaGasto"];
            $mesGasto = date("m", strtotime($fechaGasto));
            #var_dump((int)$mesGasto);

            $saldos = ModeloMaestras::mdlTraerSaldos((int)$mesGasto);
            #var_dump($saldos["estado"]);

            if ($saldos["estado"] == "CER") {

                # Mostramos una alerta suave
                echo '<script>
                        swal({
                            type: "error",
                            title: "Error",
                            text: "¡No es posible registrar gastos en un mes cerrado!",
                            showConfirmButton: true,
                            confirmButtonText: "Cerrar"
                        }).then((result)=>{
                            if(result.value){
                                window.location="gastos-caja";}
                        });
                    </script>';
            } else {

                # traemos la fecha y la pc
                date_default_timezone_set('America/Lima');
                $fecha = new DateTime();
                $PcReg = gethostbyaddr($_SERVER['REMOTE_ADDR']);
                $total = str_replace(",", "", $_POST["total"]);

                $datos = array(
                    "fecha"             =>  $_POST["fechaGasto"],
                    "recibo"            =>  $_POST["recibo"],
                    "ruc_proveedor"     =>  $_POST["nuevoRucProC"],
                    "proveedor"         =>  $_POST["nuevaRazPro"],
                    "sucursal"          =>  $_POST["nuevaSucursal"],
                    "cod_caja"          =>  $_POST["nuevoCodCaja"],
                    "total"             =>  $total,
                    "tipo_documento"    =>  $_POST["nuevoTipo"],
                    "documento"         =>  $_POST["documento"],
                    "solicitante"       =>  $_POST["solicitante"],
                    "descripcion"       =>  $_POST["descripcion"],
                    "rubro_cancelacion" =>  $_POST["rubro"],
                    "observacion"       =>  $_POST["observacion"],
                    "estado"            =>  '1',
                    "fecreg"            =>  $fecha->format("Y-m-d H:i:s"),
                    "usureg"            =>  $_SESSION["nombre"],
                    "pcreg"             =>  $PcReg
                );
                #var_dump($datos);

                $respuesta = ModeloCentroCostos::mdlCrearGastosCaja($datos);
                #var_dump($respuesta);

                if ($respuesta == "ok") {

                    $total = str_replace(",", "", $_POST["total"]);
                    $fecha = $_POST["fechaGasto"];
                    $egreso = $total;

                    $datosM = array(
                        "fecha"     => $fecha,
                        "egreso"    => $egreso
                    );

                    #var_dump($datosM);
                    ModeloCentroCostos::mdlActualizarEgresosA($datosM);
                    #var_dump($respuestaG);

                    # Mostramos una alerta suave
                    echo '<script>
                    swal({
                        type: "success",
                        title: "Felicitaciones",
                        text: "¡El gasto fue guardado con éxito!",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar"
                    }).then((result)=>{
                        if(result.value){
                            window.location="gastos-caja";}
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
                                    window.location="gastos-caja";}
                            });
                        </script>';
                }
            }
        }
    }

    /* 
    *CREAR GASTOS
    */
    static public function ctrEditarGastosCaja()
    {

        if (isset($_POST["id"])) {

            $fechaGasto = $_POST["editarFechaGasto"];
            $mesGasto = date("m", strtotime($fechaGasto));
            #var_dump((int)$mesGasto);

            $saldos = ModeloMaestras::mdlTraerSaldos((int)$mesGasto);
            #var_dump($saldos["estado"]);

            if ($saldos["estado"] == "CER") {

                # Mostramos una alerta suave
                echo '<script>
                        swal({
                            type: "error",
                            title: "Error",
                            text: "¡No es posible editar gastos en un mes cerrado!",
                            showConfirmButton: true,
                            confirmButtonText: "Cerrar"
                        }).then((result)=>{
                            if(result.value){
                                window.location="gastos-caja";}
                        });
                    </script>';
            } else {

                # traemos la fecha y la pc
                date_default_timezone_set('America/Lima');
                $fecha = new DateTime();
                $PcReg = gethostbyaddr($_SERVER['REMOTE_ADDR']);
                $total = str_replace(",", "", $_POST["editarTotal"]);

                $datos = array(
                    "id"                =>  $_POST["id"],
                    "fecha"             =>  $_POST["editarFechaGasto"],
                    "recibo"            =>  $_POST["editarRecibo"],
                    "ruc_proveedor"     =>  $_POST["editarRucProC"],
                    "proveedor"         =>  $_POST["editarRazPro"],
                    "sucursal"          =>  $_POST["editarSucursal"],
                    "cod_caja"          =>  $_POST["editarCodCaja"],
                    "total"             =>  $total,
                    "tipo_documento"    =>  $_POST["editarTipo"],
                    "documento"         =>  $_POST["editarDocumentoG"],
                    "solicitante"       =>  $_POST["editarSolicitante"],
                    "descripcion"       =>  $_POST["editarDescripcion"],
                    "rubro_cancelacion" =>  $_POST["editarRubro"],
                    "observacion"       =>  $_POST["editarObservacion"],
                    "fecmod"            =>  $fecha->format("Y-m-d H:i:s"),
                    "usumod"            =>  $_SESSION["nombre"],
                    "pcmod"             =>  $PcReg
                );
                var_dump($datos);

                $respuesta = ModeloCentroCostos::mdlEditarGastosCaja($datos);
                #var_dump($respuesta);

                if ($respuesta == "ok") {

                    $total = str_replace(",", "", $_POST["editarTotal"]);
                    $fecha = $_POST["editarFechaGasto"];
                    $nuevo = $total;
                    $antiguo = $_POST["totalAntiguo"];

                    $datosM = array(
                        "fecha"     => $fecha,
                        "nuevo"     => $nuevo,
                        "antiguo"   => $antiguo
                    );

                    #var_dump($datosM);
                    $respuestaG = ModeloCentroCostos::mdlActualizarEgresosB($datosM);
                    #var_dump($respuestaG);

                    # Mostramos una alerta suave
                    echo '<script>
                            swal({
                                type: "success",
                                title: "Felicitaciones",
                                text: "¡El gasto fue editado con éxito!",
                                showConfirmButton: true,
                                confirmButtonText: "Cerrar"
                            }).then((result)=>{
                                if(result.value){
                                    window.location="gastos-caja";}
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
                                    window.location="gastos-caja";}
                            });
                        </script>';
                }
            }
        }
    }

    /* 
    *ANULAR GASTO
    */
    static public function ctrAnularGasto()
    {

        if (isset($_GET["idGasto"])) {

            $id = $_GET["idGasto"];
            #var_dump($id);

            $gasto = ModeloCentroCostos::mdlMostrarGastosCajaId($id);
            #var_dump($gasto["fecha"]);

            $fechaGasto = $gasto["fecha"];
            $mesGasto = date("m", strtotime($fechaGasto));
            #var_dump((int)$mesGasto);

            $saldos = ModeloMaestras::mdlTraerSaldos((int)$mesGasto);
            #var_dump($saldos["estado"]); 

            if ($saldos["estado"] == "CER") {

                # Mostramos una alerta suave
                echo '<script>
                    swal({
                        type: "error",
                        title: "Error",
                        text: "¡No es posible eliminar gastos en un mes cerrado!",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar"
                    }).then((result)=>{
                        if(result.value){
                            window.location="gastos-caja";}
                    });
                </script>';
            } else {

                # traemos la fecha y la pc
                date_default_timezone_set('America/Lima');
                $fecha = new DateTime();
                $PcReg = gethostbyaddr($_SERVER['REMOTE_ADDR']);

                $datos = array(
                    "id"        => $id,
                    "fecanu"    =>  $fecha->format("Y-m-d H:i:s"),
                    "usuanu"    =>  $_SESSION["nombre"],
                    "pcanu"     =>  $PcReg
                );
                #var_dump($datos);

                $respuesta = ModeloCentroCostos::mdlAnularGastosCaja($datos);
                #var_dump($respuesta);

                if ($respuesta == "ok") {

                    $fecha = $gasto["fecha"];
                    $egreso = $gasto["total"];

                    $datosM = array(
                        "fecha"     => $fecha,
                        "egreso"    => $egreso
                    );

                    #var_dump($datosM);
                    ModeloCentroCostos::mdlActualizarEgresosC($datosM);
                    #var_dump($respuestaG);

                    echo '<script>

                    swal({
                        type: "success",
                        title: "El gasto ha sido anulada correctamente",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar",
                        closeOnConfirm: false
                        }).then(function(result){
                                    if (result.value) {

                                    window.location = "gastos-caja";

                                    }
                                })

                    </script>';
                }
            }
        }
    }

    /* 
    *Mostrar Correlativo
    */
    static public function ctrMostrarMeses()
    {

        $respuesta = ModeloCentroCostos::mdlMostrarMeses();

        return $respuesta;
    }

    /* 
    * CERRAR MES
    */
    static public function ctrCerrarMesG()
    {

        if (isset($_POST["mesCerrar"])) {

            $mes = $_POST["mesCerrar"];

            $respuesta = ModeloCentroCostos::mdlCerrarMes($mes);
            #var_dump($respuesta);

            $datosMes = ModeloMaestras::mdlTraerSaldos($mes);
            #var_dump($datosMes);

            $saldo_actual = $datosMes["actual"];
            #var_dump($saldo_actual);

            $abrirMes = ModeloCentroCostos::mdlAbrirMes($mes, $saldo_actual);
            #var_dump($abrirMes);

            if ($abrirMes == "ok") {

                $mesAbierto = ModeloMaestras::mdlTraerSaldos($mes + 1);
                var_dump($mesAbierto);

                echo '<script>

				swal({
					  type: "success",
					  title: "Se abrio ' . $mesAbierto["nom_mes"] . ' con saldo inicial de S/ ' . $mesAbierto["saldo_inicial"] . '",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar",
					  closeOnConfirm: false
					  }).then(function(result){
								if (result.value) {

								window.location = "gastos-caja";

								}
							})

				</script>';
            }
        }
    }

    /* 
    * CERRAR MES
    */
    static public function ctrCerrarMesI()
    {

        if (isset($_POST["mesCerrar"])) {

            $mes = $_POST["mesCerrar"];

            $respuesta = ModeloCentroCostos::mdlCerrarMes($mes);
            #var_dump($respuesta);

            $datosMes = ModeloMaestras::mdlTraerSaldos($mes);
            #var_dump($datosMes);

            $saldo_actual = $datosMes["actual"];
            #var_dump($saldo_actual);

            $abrirMes = ModeloCentroCostos::mdlAbrirMes($mes, $saldo_actual);
            #var_dump($abrirMes);

            if ($abrirMes == "ok") {

                $mesAbierto = ModeloMaestras::mdlTraerSaldos($mes + 1);
                var_dump($mesAbierto);

                echo '<script>

				swal({
					  type: "success",
					  title: "Se abrio ' . $mesAbierto["nom_mes"] . ' con saldo inicial de S/ ' . $mesAbierto["saldo_inicial"] . '",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar",
					  closeOnConfirm: false
					  }).then(function(result){
								if (result.value) {

								window.location = "ingresos-caja";

								}
							})

				</script>';
            }
        }
    }

    /*
	*MOSTRAR INGRESOS DE CAJA
	*/
    static public function ctrMostrarIngresosCaja($mes)
    {

        $respuesta = ModeloCentroCostos::mdlMostrarIngresosCaja($mes);

        return $respuesta;
    }

    /*
	*MOSTRAR INGRESOS DE CAJA
	*/
    static public function ctrMostrarIngresosVendedor($mes)
    {

        $respuesta = ModeloCentroCostos::mdlMostrarIngresosVendedor($mes);

        return $respuesta;
    }

    /* 
    *CREAR INGRESOS
    */
    static public function ctrCrearIngresosCaja()
    {

        if (isset($_POST["nuevoCodIng"])) {

            $fechaIngreso = $_POST["fechaIngreso"];
            $mesIngreso = date("m", strtotime($fechaIngreso));
            #var_dump((int)$mesIngreso);

            $saldos = ModeloMaestras::mdlTraerSaldos((int)$mesIngreso);
            #var_dump($saldos["estado"]);

            if ($saldos["estado"] == "CER") {

                # Mostramos una alerta suave
                echo '<script>
                        swal({
                            type: "error",
                            title: "Error",
                            text: "¡No es posible registrar ingresos en un mes cerrado!",
                            showConfirmButton: true,
                            confirmButtonText: "Cerrar"
                        }).then((result)=>{
                            if(result.value){
                                window.location="ingresos-caja";}
                        });
                    </script>';
            } else {

                # traemos la fecha y la pc
                date_default_timezone_set('America/Lima');
                $fecha = new DateTime();
                $PcReg = gethostbyaddr($_SERVER['REMOTE_ADDR']);
                $total = str_replace(",", "", $_POST["nuevoTotal"]);

                $datos = array(
                    "fecha"             =>  $_POST["fechaIngreso"],
                    "cod_ingreso"       =>  $_POST["nuevoCodIng"],
                    "cod_responsable"   =>  $_POST["nuevoResp"],
                    "tipo_documento"    =>  $_POST["nuevoTipo"],
                    "documento"         =>  $_POST["documento"],
                    "total"             =>  $total,
                    "observacion"       =>  $_POST["observacion"],
                    "fecreg"            =>  $fecha->format("Y-m-d H:i:s"),
                    "usureg"            =>  $_SESSION["nombre"],
                    "pcreg"             =>  $PcReg
                );
                #var_dump($datos);

                $respuesta = ModeloCentroCostos::mdlCrearIngresosCaja($datos);
                #var_dump($respuesta);     

                if ($respuesta == "ok") {

                    $total = str_replace(",", "", $_POST["nuevoTotal"]);
                    $fecha = $_POST["fechaIngreso"];
                    $ingreso = $total;

                    $datosM = array(
                        "fecha"     => $fecha,
                        "ingreso"   => $ingreso
                    );

                    #var_dump($datosM);
                    ModeloCentroCostos::mdlActualizarIngresosA($datosM);
                    #var_dump($respuestaG);

                    # Mostramos una alerta suave
                    echo '<script>
                            swal({
                                type: "success",
                                title: "Felicitaciones",
                                text: "¡El ingreso fue registrado con éxito!",
                                showConfirmButton: true,
                                confirmButtonText: "Cerrar"
                            }).then((result)=>{
                                if(result.value){
                                    window.location="ingresos-caja";}
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
                                    window.location="ingresos-caja";}
                            });
                        </script>';
                }
            }
        }
    }

    /* 
    *CREAR GASTOS
    */
    static public function ctrEditarIngresosCaja()
    {

        if (isset($_POST["id"])) {

            $fechaIngreso = $_POST["editarFechaIngreso"];
            $mesIngreso = date("m", strtotime($fechaIngreso));
            var_dump((int)$mesIngreso);

            $saldos = ModeloMaestras::mdlTraerSaldos((int)$mesIngreso);
            var_dump($saldos["estado"]);

            if ($saldos["estado"] == "CER") {

                # Mostramos una alerta suave
                echo '<script>
                        swal({
                            type: "error",
                            title: "Error",
                            text: "¡No es posible editar gastos en un mes cerrado!",
                            showConfirmButton: true,
                            confirmButtonText: "Cerrar"
                        }).then((result)=>{
                            if(result.value){
                                window.location="gastos-caja";}
                        });
                    </script>';
            } else {

                # traemos la fecha y la pc
                date_default_timezone_set('America/Lima');
                $fecha = new DateTime();
                $PcReg = gethostbyaddr($_SERVER['REMOTE_ADDR']);
                $total = str_replace(",", "", $_POST["editarTotal"]);

                $datos = array(
                    "id"                =>  $_POST["id"],
                    "fecha"             =>  $_POST["editarFechaIngreso"],
                    "cod_ingreso"       =>  $_POST["editarCodIng"],
                    "cod_responsable"   =>  $_POST["editarResp"],
                    "tipo_documento"    =>  $_POST["editarTipo"],
                    "documento"         =>  $_POST["editarDocumentoI"],
                    "total"             =>  $total,
                    "observacion"       =>  $_POST["editarObservacion"],
                    "fecmod"            =>  $fecha->format("Y-m-d H:i:s"),
                    "usumod"            =>  $_SESSION["nombre"],
                    "pcmod"             =>  $PcReg
                );
                #var_dump($datos);

                $respuesta = ModeloCentroCostos::mdlEditarIngresosCaja($datos);
                #var_dump($respuesta);

                if ($respuesta == "ok") {

                    $total = str_replace(",", "", $_POST["editarTotal"]);
                    $fecha = $_POST["editarFechaIngreso"];
                    $nuevo = $total;
                    $antiguo = $_POST["totalAntiguo"];

                    $datosM = array(
                        "fecha"     => $fecha,
                        "nuevo"     => $nuevo,
                        "antiguo"   => $antiguo
                    );

                    var_dump($datosM);
                    $respuestaG = ModeloCentroCostos::mdlActualizarIngresosB($datosM);
                    var_dump($respuestaG);

                    # Mostramos una alerta suave
                    echo '<script>
                        swal({
                            type: "success",
                            title: "Felicitaciones",
                            text: "¡El ingreso fue editado con éxito!",
                            showConfirmButton: true,
                            confirmButtonText: "Cerrar"
                        }).then((result)=>{
                            if(result.value){
                                window.location="ingresos-caja";}
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
                                    window.location="ingresos-caja";}
                            });
                        </script>';
                }
            }
        }
    }

    /* 
    *ANULAR GASTO
    */
    static public function ctrAnularIngreso()
    {

        if (isset($_GET["idIngreso"])) {

            $id = $_GET["idIngreso"];
            #var_dump($id);

            $gasto = ModeloCentroCostos::mdlMostrarIngresosCajaId($id);
            #var_dump($gasto["fecha"]);

            $fechaIngreso = $gasto["fecha"];
            $mesIngreso = date("m", strtotime($fechaIngreso));
            #var_dump((int)$mesIngreso);

            $saldos = ModeloMaestras::mdlTraerSaldos((int)$mesIngreso);
            #var_dump($saldos["estado"]); 

            if ($saldos["estado"] == "CER") {

                # Mostramos una alerta suave
                echo '<script>
                    swal({
                        type: "error",
                        title: "Error",
                        text: "¡No es posible eliminar gastos en un mes cerrado!",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar"
                    }).then((result)=>{
                        if(result.value){
                            window.location="gastos-caja";}
                    });
                </script>';
            } else {

                #traemos la fecha y la pc
                date_default_timezone_set('America/Lima');
                $fecha = new DateTime();
                $PcReg = gethostbyaddr($_SERVER['REMOTE_ADDR']);

                $datos = array(
                    "id"        =>  $id,
                    "fecanu"    =>  $fecha->format("Y-m-d H:i:s"),
                    "usuanu"    =>  $_SESSION["nombre"],
                    "pcanu"     =>  $PcReg
                );
                #var_dump($datos);

                $respuesta = ModeloCentroCostos::mdlAnularIngresosCaja($datos);
                #var_dump($respuesta);     

                if ($respuesta == "ok") {

                    $fecha = $gasto["fecha"];
                    $ingreso = $gasto["total"];

                    $datosM = array(
                        "fecha"     => $fecha,
                        "ingreso"    => $ingreso
                    );

                    #var_dump($datosM);
                    ModeloCentroCostos::mdlActualizarIngresosC($datosM);
                    #var_dump($respuestaG);

                    echo '<script>

                    swal({
                        type: "success",
                        title: "El ingreso ha sido anulado correctamente",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar",
                        closeOnConfirm: false
                        }).then(function(result){
                                    if (result.value) {

                                    window.location = "ingresos-caja";

                                    }
                                })

                    </script>';
                }
            }
        }
    }

    /* 
    *CREAR SOLICITUD
    */
    static public function ctrCrearGastosSolicitud()
    {

        if (isset($_POST["recibo"])) {

            $fechaSolicitud = $_POST["fechaSolicitud"];
            $mesSolicitud = date("m", strtotime($fechaSolicitud));
            #var_dump((int)$mesSolicitud);

            $saldos = ModeloMaestras::mdlTraerSaldos((int)$mesSolicitud);
            #var_dump($saldos["estado"]);

            if ($saldos["estado"] == "CER") {

                # Mostramos una alerta suave
                echo '<script>
                        swal({
                            type: "error",
                            title: "Error",
                            text: "¡No es posible registrar gastos en un mes cerrado!",
                            showConfirmButton: true,
                            confirmButtonText: "Cerrar"
                        }).then((result)=>{
                            if(result.value){
                                window.location="solicitud-caja";}
                        });
                    </script>';
            } else {

                # traemos la fecha y la pc
                date_default_timezone_set('America/Lima');
                $fecha = new DateTime();
                $PcReg = gethostbyaddr($_SERVER['REMOTE_ADDR']);
                $total = str_replace(",", "", $_POST["totalSol"]);

                $datos = array(
                    "fecha"             =>  $_POST["fechaSolicitud"],
                    "recibo"            =>  $_POST["recibo"],
                    "ruc_proveedor"     =>  $_POST["nuevoRucProSol"],
                    "proveedor"         =>  $_POST["nuevaRazPro"],
                    "sucursal"          =>  $_POST["nuevaSucursal"],
                    "cod_caja"          =>  $_POST["nuevoCodCajaSol"],
                    "total"             =>  $total,
                    "tipo_documento"    =>  '99',
                    "documento"         =>  'POR RENDIR',
                    "solicitante"       =>  $_POST["solicitante"],
                    "descripcion"       =>  $_POST["descripcion"],
                    "rubro_cancelacion" =>  $_POST["rubro"],
                    "observacion"       =>  $_POST["observacion"],
                    "estado"            =>  '2',
                    "fecreg"            =>  $fecha->format("Y-m-d H:i:s"),
                    "usureg"            =>  $_SESSION["nombre"],
                    "pcreg"             =>  $PcReg
                );
                var_dump($datos);

                $respuesta = ModeloCentroCostos::mdlCrearGastosCaja($datos);
                var_dump($respuesta);

                if ($respuesta == "ok") {

                    ModeloCentroCostos::mdlActualizarCorrelativo();

                    # Mostramos una alerta suave
                    echo '<script>
                            swal({
                                type: "success",
                                title: "Felicitaciones",
                                text: "¡La solicitud fue guardada con éxito!",
                                showConfirmButton: true,
                                confirmButtonText: "Cerrar"
                            }).then((result)=>{
                                if(result.value){
                                    window.location="solicitud-caja";}
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
                                    window.location="solicitud-caja";}
                            });
                        </script>';
                }
            }
        }
    }

    static public function ctrEditarSolicitudCaja()
    {

        if (isset($_POST["id"])) {

            $fechaSolicitud = $_POST["editarFechaSol"];
            $mesSolicitud = date("m", strtotime($fechaSolicitud));
            ##var_dump((int)$mesSolicitud);

            $saldos = ModeloMaestras::mdlTraerSaldos((int)$mesSolicitud);
            #var_dump($saldos["estado"]);

            if ($saldos["estado"] == "CER") {

                # Mostramos una alerta suave
                echo '<script>
                        swal({
                            type: "error",
                            title: "Error",
                            text: "¡No es posible editar gastos en un mes cerrado!",
                            showConfirmButton: true,
                            confirmButtonText: "Cerrar"
                        }).then((result)=>{
                            if(result.value){
                                window.location="solicitud-caja";}
                        });
                    </script>';
            } else {

                # traemos la fecha y la pc
                date_default_timezone_set('America/Lima');
                $fecha = new DateTime();
                $PcReg = gethostbyaddr($_SERVER['REMOTE_ADDR']);

                if ($_POST["estado"] == "2") {

                    $total = str_replace(",", "", $_POST["editarTotalS"]);
                } else {

                    $total = $_POST["totalAntiguo"];
                }

                $datos = array(
                    "id"                =>  $_POST["id"],
                    "fecha"             =>  $_POST["editarFechaSol"],
                    "recibo"            =>  $_POST["editarRecibo"],
                    "ruc_proveedor"     =>  $_POST["editarRucProSol"],
                    "proveedor"         =>  $_POST["editarRazPro"],
                    "sucursal"          =>  $_POST["editarSucursalSol"],
                    "cod_caja"          =>  $_POST["editarCodCajaSol"],
                    "total"             =>  $total,
                    "tipo_documento"    =>  $_POST["editarTipoSol"],
                    "documento"         =>  $_POST["editarDocumentoS"],
                    "solicitante"       =>  $_POST["editarSolicitante"],
                    "descripcion"       =>  $_POST["editarDescripcion"],
                    "rubro_cancelacion" =>  $_POST["editarRubro"],
                    "observacion"       =>  $_POST["editarObservacion"],
                    "fecmod"            =>  $fecha->format("Y-m-d H:i:s"),
                    "usumod"            =>  $_SESSION["nombre"],
                    "pcmod"             =>  $PcReg
                );
                #var_dump($datos);

                $respuesta = ModeloCentroCostos::mdlEditarGastosCaja($datos);
                #var_dump($respuesta);

                if ($respuesta == "ok") {

                    # Mostramos una alerta suave
                    echo '<script>
                            swal({
                                type: "success",
                                title: "Felicitaciones",
                                text: "¡El gasto fue editado con éxito!",
                                showConfirmButton: true,
                                confirmButtonText: "Cerrar"
                            }).then((result)=>{
                                if(result.value){
                                    window.location="solicitud-caja";}
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
                                    window.location="solicitud-caja";}
                            });
                        </script>';
                }
            }
        }
    }

    /* 
    *ANULAR GASTO - SOLICITUD
    */
    static public function ctrAnularSolicitud()
    {

        if (isset($_GET["idGasto"])) {

            $id = $_GET["idGasto"];
            #var_dump($id);

            $gasto = ModeloCentroCostos::mdlMostrarGastosCajaId($id);
            #var_dump($gasto["fecha"]);

            $fechaGasto = $gasto["fecha"];
            $mesGasto = date("m", strtotime($fechaGasto));
            #var_dump((int)$mesGasto);

            $saldos = ModeloMaestras::mdlTraerSaldos((int)$mesGasto);
            #var_dump($saldos["estado"]); 

            if ($saldos["estado"] == "CER") {

                # Mostramos una alerta suave
                echo '<script>
                    swal({
                        type: "error",
                        title: "Error",
                        text: "¡No es posible eliminar gastos en un mes cerrado!",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar"
                    }).then((result)=>{
                        if(result.value){
                            window.location="solicitud-caja";}
                    });
                </script>';
            } else {

                # traemos la fecha y la pc
                date_default_timezone_set('America/Lima');
                $fecha = new DateTime();
                $PcReg = gethostbyaddr($_SERVER['REMOTE_ADDR']);

                $datos = array(
                    "id"        => $id,
                    "fecanu"    =>  $fecha->format("Y-m-d H:i:s"),
                    "usuanu"    =>  $_SESSION["nombre"],
                    "pcanu"     =>  $PcReg
                );
                #var_dump($datos);

                $respuesta = ModeloCentroCostos::mdlAnularGastosCaja($datos);
                #var_dump($respuesta);

                if ($respuesta == "ok") {

                    echo '<script>

                    swal({
                        type: "success",
                        title: "La solicitud ha sido anulada correctamente",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar",
                        closeOnConfirm: false
                        }).then(function(result){
                                    if (result.value) {

                                    window.location = "solicitud-caja";

                                    }
                                })

                    </script>';
                }
            }
        }
    }

    /* 
    *Mostrar diarios
    */
    static public function ctrMostrarDiario($valor)
    {

        $respuesta = ModeloCentroCostos::mdlMostrarDiario($valor);

        return $respuesta;
    }

    /* 
    *Mostrar diarios - Alerta
    */
    static public function ctrMostrarDiarioAlerta()
    {

        $respuesta = ModeloCentroCostos::mdlMostrarDiarioAlerta();

        return $respuesta;
    }

    /* 
    *Mostrar diarios - Alerta
    */
    static public function ctrMostrarRegCompras()
    {

        $respuesta = ModeloCentroCostos::mdlMostrarRegCompras();

        return $respuesta;
    }

    /* 
    *Mostrar KARDEX
    */
    static public function ctrMostrarKardex($valor, $tipo = "", $anno = "", $mes = "")
    {

        $respuesta = ModeloCentroCostos::mdlMostrarKardex($valor, $tipo, $anno, $mes);

        return $respuesta;
    }

    /* 
    *Mostrar diarios - Alerta
    */
    static public function ctrCargarKardex()
    {

        if (isset($_POST["tipoKardex"])) {

            //*Crear la Cabecera
            date_default_timezone_set('America/Lima');
            $fecha = new DateTime();
            $pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);
            $usureg = $_SESSION["nombre"];

            $codigo = $_POST["tipoKardex"] . $_POST["anno"] . substr($_POST["mes"], 0, 3);

            $datosCab = array(
                "tipo"          => $_POST["tipoKardex"],
                "codigo"        => $codigo,
                "anno"          => $_POST["anno"],
                "mes"           => $_POST["mes"],
                "saldo_inicial" => $_POST["saldo_inicial"],
                "ingreso"       => $_POST["ingresos"],
                "salida"        => $_POST["salidas"],
                "saldo_final"   => $_POST["saldo_final"],
                "usureg"        => $usureg,
                "pcreg"         => $pcreg,
                "fecreg"        => $fecha->format("Y-m-d H:i:s")
            );
            #var_dump($datosCab);
            $respuestaCab = ModeloCentroCostos::mdlIngresarKardexCab($datosCab);
            #var_dump($respuestaCab);


            //*Crear detalle

            include "/../vistas/reportes_excel/Excel/reader.php";

            $directorio = "vistas/contabilidad/kardex/" . $_FILES["archivo"]["name"];
            $archivo = move_uploaded_file($_FILES["archivo"]['tmp_name'], $directorio);
            $data = new Spreadsheet_Excel_Reader();
            $data->setOutputEncoding('CP1251');


            $data->read("vistas/contabilidad/kardex/" . $_FILES["archivo"]["name"]);
            /* $con=ControladorUsuarios::ctrMostrarConexiones("id",1);
            $conexion = mysql_connect($con["ip"], $con["user"], $con["pwd"]) or die("No se pudo conectar: " . mysql_error());
            mysql_select_db($con["db"], $conexion); */

            $columnas = $data->sheets[0]['numRows'];
            #var_dump($columnas);

            error_reporting(0);

            $intoA = "";
            for ($i = 2; $i <= $columnas; $i++) {


                $fechaK         = $data->sheets[0]['cells'][$i][1];
                $ttabla         = $data->sheets[0]['cells'][$i][2];
                $estab          = $data->sheets[0]['cells'][$i][3];
                $num_doc        = $data->sheets[0]['cells'][$i][4];
                $item           = utf8_decode($data->sheets[0]['cells'][$i][5]);
                $descripcion    = utf8_decode($data->sheets[0]['cells'][$i][6]);
                $coduni         = $data->sheets[0]['cells'][$i][7];
                $unidad         = $data->sheets[0]['cells'][$i][8];
                $cliente        = $data->sheets[0]['cells'][$i][9];
                $ctm            = $data->sheets[0]['cells'][$i][10];
                $tipo_mov       = $data->sheets[0]['cells'][$i][11];

                $cantidadA      = $data->sheets[0]['cells'][$i][12];
                $puA            = $data->sheets[0]['cells'][$i][13];
                $totalA         = $data->sheets[0]['cells'][$i][14];

                $cantidadB      = $data->sheets[0]['cells'][$i][15];
                $puB            = $data->sheets[0]['cells'][$i][16];
                $totalB         = $data->sheets[0]['cells'][$i][17];

                $cantidadC      = $data->sheets[0]['cells'][$i][18];
                $puC            = $data->sheets[0]['cells'][$i][19];
                $totalC         = $data->sheets[0]['cells'][$i][20];

                /* $area           = $data->sheets[0]['cells'][$i][21];
                $cuenta         = $data->sheets[0]['cells'][$i][22];
                $auxA           = $data->sheets[0]['cells'][$i][23];
                $nom_cuenta     = $data->sheets[0]['cells'][$i][24];
                $auxB           = $data->sheets[0]['cells'][$i][25]; */

                $intoA .= "('" . strtoupper($codigo) . "','" .
                    $fechaK . "','" .
                    $ttabla . "','" .
                    $estab . "','" .
                    $num_doc . "','" .
                    str_replace('?', 'Ñ', $item) . "','" .
                    $descripcion . "','" .
                    $coduni . "','" .
                    $unidad . "','" .
                    $cliente . "','" .
                    $ctm . "','" .
                    $tipo_mov . "','" .

                    $cantidadA . "','" .
                    $puA . "','" .
                    $totalA . "','" .

                    $cantidadB . "','" .
                    $puB . "','" .
                    $totalB . "','" .

                    $cantidadC . "','" .
                    $puC . "','" .
                    $totalC . "'" .

                    /* $area."','".
                                $cuenta."','".
                                $auxA."','".
                                $nom_cuenta."','".
                                $auxB."'".   */
                    "),";
            }

            #var_dump($intoA);

            $detalle = substr($intoA, 0, -1);
            var_dump("detalle", $detalle);

            $respuestaDetalle = ModeloCentroCostos::mdlIngresarKardexDet($detalle);
            var_dump($respuestaDetalle);

            if ($respuestaDetalle == "ok") {

                echo '<script>

				swal({
					type: "success",
					title: "Se cargo correctamente",
					showConfirmButton: true,
					confirmButtonText: "Cerrar"
					}).then(function(result){
								if (result.value) {

								window.location = "kardex-carga";

								}
							})

				</script>';
            }
        }
    }


    //* CARGAR KARDEX

    static public function ctrCargarKardexB()
    {
        if (isset($_POST["tipoKardex"])) {

            if (isset($_POST["tipoKardex"])) {

                date_default_timezone_set('America/Lima');
                $fecha = new DateTime();
                $pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);
                $usureg = $_SESSION["nombre"];
                $codigo = $_POST["tipoKardex"] . $_POST["anno"] . substr($_POST["mes"], 0, 3);

                $datosCab = [
                    "tipo"          => $_POST["tipoKardex"],
                    "codigo"        => $codigo,
                    "anno"          => $_POST["anno"],
                    "mes"           => $_POST["mes"],
                    "saldo_inicial" => $_POST["saldo_inicial"],
                    "ingreso"       => $_POST["ingresos"],
                    "salida"        => $_POST["salidas"],
                    "saldo_final"   => $_POST["saldo_final"],
                    "usureg"        => $usureg,
                    "pcreg"         => $pcreg,
                    "fecreg"        => $fecha->format("Y-m-d H:i:s")
                ];

                $respuestaCab = ModeloCentroCostos::mdlIngresarKardexCab($datosCab);

                // Leer Excel
                include "/../vistas/reportes_excel/Excel/reader.php";

                $directorio = "vistas/contabilidad/kardex/" . $_FILES["archivo"]["name"];
                move_uploaded_file($_FILES["archivo"]['tmp_name'], $directorio);

                $data = new Spreadsheet_Excel_Reader();
                $data->setOutputEncoding('CP1251');
                $data->read($directorio);

                $columnas = $data->sheets[0]['numRows'];

                $detalle = "";
                $intoA = "";
                for ($i = 2; $i <= $columnas; $i++) {
                    $rowData = [];

                    for ($j = 1; $j <= 20; $j++) { // Suponiendo que tienes 21 columnas
                        if (isset($data->sheets[0]['cells'][$i][$j])) {
                            $cellValue = $data->sheets[0]['cells'][$i][$j];
                        } else {
                            $cellValue = null;
                        }

                        if ($cellValue === null) {
                            $rowData[] = "";  // Insertar un valor vacío si la celda está vacía
                        } else {
                            $rowData[] = $cellValue;
                        }
                    }

                    $insertStatement = self::createInsertStatement($rowData, $codigo);
                    if (!empty($insertStatement)) {
                        $intoA .= $insertStatement;
                    }
                }


                $detalle = rtrim($intoA, ',');  // Remover la última coma


                $respuestaDetalle = ModeloCentroCostos::mdlIngresarKardexDet($detalle);

                if ($respuestaDetalle == "ok") {

                    echo '<script>

                    swal({
                        type: "success",
                        title: "Se cargo correctamente",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar"
                        }).then(function(result){
                                    if (result.value) {

                                    window.location = "kardex-carga";

                                    }
                                })

                    </script>';
                }
            }
        }
    }


    public static function createInsertStatement($rowData, $codigo)
    {
        $values = array_map(function ($item) {
            return "'" . utf8_encode(addslashes($item)) . "'";
        }, $rowData);

        if (count($rowData) === 20) {
            return sprintf("(%s),", implode(",", array_merge(["'" . strtoupper($codigo) . "'"], $values)));
        } else {
            return '';  // O maneja este caso como consideres más apropiado
        }
    }




    /* 
    *Mostrar KARDEX
    */
    static public function ctrVerItems($valor)
    {

        $respuesta = ModeloCentroCostos::mdlVerItems($valor);

        return $respuesta;
    }

    /* 
    *Mostrar KARDEX
    */
    static public function ctrVerItemsDet($codigo, $item)
    {

        $respuesta = ModeloCentroCostos::mdlVerItemsDet($codigo, $item);

        return $respuesta;
    }
}
