<?php

class ControladorMantenimiento
{

    //*MOSTRAR EQUIPOS
    static public function ctrMostrarEquipos($valor)
    {

        $respuesta = ModeloMantenimiento::mdlMostrarEquipos($valor);

        return $respuesta;
    }

    //*TRAER EL UTIMO CODIGO 
    static public function ctrTraerUltCod($valor)
    {

        $respuesta = ModeloMantenimiento::mdlTraerUltCod($valor);

        return $respuesta;
    }

    //*CREAR MAQUINA
    static public function ctrCrearMaquina()
    {

        if (isset($_POST["nuevoCodTipo"])) {

            #var_dump($_POST["nuevoCodTipo"]);

            # traemos la fecha y la pc
            date_default_timezone_set('America/Lima');
            $usureg = $_SESSION["nombre"];
            $pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);
            $fecreg = new DateTime();


            $datos = array(
                "cod_tipo"          => $_POST["nuevoCodTipo"],
                "cod_tip_maquina"   => $_POST["nuevoTipMaq"],
                "descripcion"       => $_POST["nuevaDescripcion"],
                "cod_ubicacion"     => $_POST["nuevaUbicacion"],
                "cod_marca_equi"    => $_POST["nuevaMarcaMaq"],
                "modelo_equipo"     => $_POST["nuevoModeloMaq"],
                "serie_equipo"      => $_POST["nuevaSerieMaq"],
                "tipo_motor"        => $_POST["nuevoTipoMotor"],
                "cod_marca_motor"   => $_POST["nuevaMarcaMaq"],
                "modelo_motor"      => $_POST["nuevoModeloMotor"],
                "serie_motor"       => $_POST["nuevoSerieMotor"],
                "cod_marca_caja"    => $_POST["nuevaMarcaCaja"],
                "modelo_caja"       => $_POST["nuevoModeloCaja"],
                "serie_caja"        => $_POST["nuevaSerieCaja"],
                "documento"         => $_POST["nuevoDocumento"],
                "ruc"               => $_POST["nuevoRuc"],
                "fecha_emision"      => $_POST["nuevoFecEmision"],
                "estado"            => $_POST["nuevoEstado"],
                "observaciones"     => $_POST["nuevaObservacion"],
                "fec_ult_mant"      => $_POST["nuevoUltimoMantenimiento"],
                "fec_pro_mant"      => $_POST["nuevoProgMantenimiento"],
                "usureg"            => $usureg,
                "pcreg"             => $pcreg,
                "fecreg"            => $fecreg->format("Y-m-d H:i:s")
            );

            var_dump($datos);
            $respuesta = ModeloMantenimiento::mdlCrearMaquina($datos);
            #var_dump($respuesta);

            if ($respuesta == "ok") {

                if ($_POST["nuevoProgMantenimiento"] != null) {

                    $datosCalendario = array(
                        "tipo"          => 'Mantenimiento',
                        "titulo"        => $_POST["nuevoCodTipo"],
                        "cod_interno"   => $_POST["nuevoCodTipo"],
                        "inicio"        => $_POST["nuevoProgMantenimiento"],
                        "fin"           => '',
                        "allday"        => '',
                        "dirurl"        => 'index.php?ruta=equipos',
                        "indicaciones"  => 'Mant. Preventivo',
                        "estado"        => 'Pendiente',
                        "usureg"        => $usureg,
                        "pcreg"         => $pcreg,
                        "fecreg"        => $fecreg->format("Y-m-d H:i:s")
                    );

                    #var_dump($datosCalendario);
                    $respuestaCalendario = ModeloMantenimiento::mdlCrearCalendario($datosCalendario);
                    #var_dump($respuestaCalendario);

                    $interno = ControladorMantenimiento::ctrMostrarCorrelativo();

                    $datosMantenimiento = array(
                        "cod_interno"   => $interno["correlativo"],
                        "tipo_mante"    => 'PREVENTIVO',
                        "mante_inicio"  => $_POST["nuevoProgMantenimiento"],
                        "mante_fin"     => '',
                        "cod_maquina"   => $_POST["nuevoCodTipo"],
                        "cod_ubi"       => $_POST["nuevaUbicacion"],
                        "responsable"   => '',
                        "items"         => 0,
                        "operario"      => '',
                        "observaciones" => '',
                        "estado"        => 'NO HECHO',
                        "usureg"        => $usureg,
                        "pcreg"         => $pcreg,
                        "fecreg"        => $fecreg->format("Y-m-d H:i:s")
                    );

                    #var_dump($datosMantenimiento);
                    $respuestaMantenimiento = ModeloMantenimiento::mdlCrearMantenimiento($datosMantenimiento);
                    #var_dump($respuestaMantenimiento);

                }

                echo '<script>

                swal({
    
                    type: "success",
                    title: "Se creo la nueva maquina",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                    }).then(function(result){
    
                        if (result.value) {
    
                        window.location = "equipos";
    
                        }
    
                    })
    
                </script>';
            } else {

                echo '<script>

                swal({
    
                    type: "error",
                    title: "No se creo la nueva maquina",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                    }).then(function(result){
    
                        if (result.value) {
    
                        window.location = "equipos";
    
                        }
    
                    })
    
                </script>';
            }
        }
    }

    //*MOSTRAR CALENDARIO
    static public function ctrMostrarCalendario($valor)
    {

        $respuesta = ModeloMantenimiento::mdlMostrarCalendario($valor);

        return $respuesta;
    }

    //*EDITAR MAQUINA
    static public function ctrEditarMaquina()
    {

        if (isset($_POST["editarIdEquipo"])) {

            #var_dump($_POST["editarIdEquipo"]);

            # traemos la fecha y la pc
            date_default_timezone_set('America/Lima');
            $usumod = $_SESSION["nombre"];
            $pcmod = gethostbyaddr($_SERVER['REMOTE_ADDR']);
            $fecmod = new DateTime();


            $datos = array(
                "id"                => $_POST["editarIdEquipo"],
                "descripcion"       => $_POST["editarDescripcion"],
                "cod_ubicacion"     => $_POST["editarUbicacion"],
                "cod_marca_equi"    => $_POST["editarMarcaMaq"],
                "modelo_equipo"     => $_POST["editarModeloMaq"],
                "serie_equipo"      => $_POST["editarSerieMaq"],
                "tipo_motor"        => $_POST["editarTipoMotor"],
                "cod_marca_motor"   => $_POST["editarMarcaMaq"],
                "modelo_motor"      => $_POST["editarModeloMotor"],
                "serie_motor"       => $_POST["editarSerieMotor"],
                "cod_marca_caja"    => $_POST["editarMarcaCaja"],
                "modelo_caja"       => $_POST["editarModeloCaja"],
                "serie_caja"        => $_POST["editarSerieCaja"],
                "documento"         => $_POST["editarDocumento"],
                "ruc"               => $_POST["editarRuc"],
                "fecha_emision"     => $_POST["editarFecEmision"],
                "estado"            => $_POST["editarEstado"],
                "observaciones"     => $_POST["editarObservacion"],
                "fec_ult_mant"      => $_POST["editarUltimoMantenimiento"],
                "fec_pro_mant"      => $_POST["editarProgMantenimiento"],
                "usumod"            => $usumod,
                "pcmod"             => $pcmod,
                "fecmod"            => $fecmod->format("Y-m-d H:i:s")
            );

            #var_dump($datos);
            $respuesta = ModeloMantenimiento::mdlEditarMaquina($datos);
            #var_dump($respuesta);

            $calendario = ModeloMantenimiento::mdlMostrarCalendarioPendiente($_POST["editarCodTipo"]);
            #var_dump($calendario);

            if ($_POST["editarProgMantenimiento"] != $calendario["inicio"]) {

                $datosCalendario = array(
                    "cod_interno"   => $_POST["editarCodTipo"],
                    "inicio"        => $_POST["editarProgMantenimiento"],
                    "usumod"        => $usumod,
                    "pcmod"         => $pcmod,
                    "fecmod"        => $fecmod->format("Y-m-d H:i:s")
                );

                $respuestaProg = ModeloMantenimiento::mdlActualizarCalendarioMaquina($datosCalendario);
                #var_dump($respuestaProg);

                $datosMantenimiento = array(
                    "cod_interno"   => $_POST["editarCodTipo"],
                    "inicio"        => $_POST["editarProgMantenimiento"],
                    "usumod"        => $usumod,
                    "pcmod"         => $pcmod,
                    "fecmod"        => $fecmod->format("Y-m-d H:i:s")
                );

                var_dump($datosMantenimiento);
                $respuestaProg = ModeloMantenimiento::mdlActualizarCalendarioMantenimiento($datosMantenimiento);
                var_dump($respuestaProg);
            }

            if ($respuesta == "ok") {

                echo '<script>

                swal({
    
                    type: "success",
                    title: "Se edito la nueva maquina",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                    }).then(function(result){
    
                        if (result.value) {
    
                        window.location = "equipos";
    
                        }
    
                    })
    
                </script>';
            } else {

                echo '<script>

                swal({
    
                    type: "error",
                    title: "No se edito la nueva maquina",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                    }).then(function(result){
    
                        if (result.value) {
    
                        window.location = "equipos";
    
                        }
    
                    })
    
                </script>';
            }
        }
    }

    //*MOSTRAR MANTENIMIENTO
    static public function ctrMostrarMantenimiento($valor)
    {

        $respuesta = ModeloMantenimiento::mdlMostrarMantenimiento($valor);

        return $respuesta;
    }

    //*MOSTRAR MANTENIMIENTO DETALLE
    static public function ctrMostrarMantenimientoDetalle($valor)
    {

        $respuesta = ModeloMantenimiento::mdlMostrarMantenimientoDetalle($valor);

        return $respuesta;
    }

    //*MOSTRAR MANTENIMIENTO DETALLE editar
    static public function ctrMostrarMantenimientoDetalleEditar($valor)
    {

        $respuesta = ModeloMantenimiento::mdlMostrarMantenimientoDetalleEditar($valor);

        return $respuesta;
    }

    //*MOSTRAR MANTENIMIENTO REPUESTOS
    static public function ctrMostrarMantenimientoRepuestos($valor)
    {

        $respuesta = ModeloMantenimiento::mdlMostrarMantenimientoRepuestos($valor);

        return $respuesta;
    }

    //*MOSTRAR CORRELATIVO MANTENIMIENTO
    static public function ctrMostrarCorrelativo()
    {

        $respuesta = ModeloMantenimiento::mdlMostrarCorrelativo();

        return $respuesta;
    }

    //*CREAR MANTENIMIENTO
    static public function ctrCrearMantenimiento()
    {

        if (isset($_POST["nuevoId"])) {
            // Obtener datos de usuario, PC y fecha actual
            date_default_timezone_set('America/Lima');
            $usureg = $_SESSION["nombre"];
            $pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);
            $fecreg = new DateTime();

            $listArticulos = json_decode($_POST["repuestosSeleccionados"], true);

            // Determinar formato de fecha de inicio según tipo de mantenimiento
            if ($_POST["nuevoTipo"] == "Preventivo") {
                $fechaInicio = new DateTime($_POST["nuevoInicio"]);
                $fechaIni = $fechaInicio->format("Y-m-d");
            } else {
                $fechaIni = $_POST["nuevoInicio"];
            }

            // Construir array de datos para crear el mantenimiento
            $datos = array(
                "cod_interno"   => $_POST["nuevoId"],
                "tipo_mante"    => $_POST["nuevoTipo"],
                "mante_inicio"  => $fechaIni,
                "mante_fin"     => $_POST["nuevoFin"],
                "cod_maquina"   => $_POST["nuevaMaquina"],
                "cod_ubi"       => $_POST["nuevaUbicacion"],
                "responsable"   => $_POST["nuevoResponsable"],
                "items"         => 0,
                "operario"      => $_POST["nuevoOperario"],
                "observaciones" => $_POST["nuevaObservacion"],
                "estado"        => $_POST["nuevoEstado"],
                "usureg"        => $usureg,
                "pcreg"         => $pcreg,
                "fecreg"        => $fecreg->format("Y-m-d H:i:s")
            );

            // Crear el mantenimiento
            $respuesta = ModeloMantenimiento::mdlCrearMantenimiento($datos);

            //registramos el detalla
            if ($respuesta == "ok") {

                foreach ($listArticulos as $articulos) {

                    $codInterno = $articulos["codInterno"];
                    $codpro = $articulos["codpro"];
                    $cospro = $articulos["cospro"];
                    $cantidad = $articulos["cantidad"];

                    $respuestaDetalle = ModeloMantenimiento::mdlAgregarRepuesto($codInterno, $codpro, $cantidad, $cospro, $usureg, $pcreg, $fecreg->format("Y-m-d H:i:s"));

                    $infoMateria = ModeloMateriaPrima::mdlMostrarMateriaPrima2($codpro);

                    # Actualizamos el stock en la tabla materia prima
                    $tabla = "producto";
                    $item1 = "CodAlm01";
                    $valor1 = $infoMateria["stock"] - $cantidad;
                    // var_dump($infoMateria);
                    // var_dump($valor1);
                    ModeloNotasSalidas::mdlActualizarUnDatoMateria($tabla, $item1, $valor1, $codpro);

                    $datosVta = [
                        "Item"      => 1,
                        "CanVta" => $cantidad,
                        "PreVta" => '0',
                        "FecEmi" => $fecreg->format("Y-m-d H:i:s"),
                        "DscVta" => '0',
                        "Tip" => 'NS',
                        "Ser" => '001',
                        "Nro" => '015677',
                        "Cod_Local" => '01',
                        "Cod_Entidad" => '01',
                        "Ruc" => "20551240356",
                        "CodPro" => $codpro,
                        "SugVta" => '0',
                        "EstVta" => 'P',
                        "pcosto" => 0,
                        "CenCosto" => "211",
                        "FecReg" => $fecreg->format("Y-m-d H:i:s"),
                        "PcReg" => $pcreg,
                        "UsuReg" => $_SESSION["nombre"]
                    ];
                    ModeloNotasSalidas::mdlGuardarDetalleNotaSalida("venta_det", $datosVta);
                }
            }

            // Si es preventivo, crear evento en calendario y actualizar equipo
            if ($_POST["nuevoTipo"] == "Preventivo") {
                $fechaInicio = new DateTime($_POST["nuevoInicio"]);
                $datosCalendario = array(
                    "tipo"          => 'Mantenimiento',
                    "titulo"        => $_POST["nuevaMaquina"],
                    "cod_interno"   => $_POST["nuevaMaquina"],
                    "inicio"        => $fechaInicio->format("Y-m-d"),
                    "fin"           => '',
                    "allday"        => '',
                    "dirurl"        => 'index.php?ruta=equipos',
                    "indicaciones"  => 'Mant. Preventivo',
                    "estado"        => 'Pendiente',
                    "usureg"        => $usureg,
                    "pcreg"         => $pcreg,
                    "fecreg"        => $fecreg->format("Y-m-d H:i:s")
                );
                ModeloMantenimiento::mdlCrearCalendario($datosCalendario);

                // Actualizar fecha de próximo mantenimiento en el equipo
                $datosEquipo = array(
                    "fec_pro_mant"  => $fechaInicio->format("Y-m-d"),
                    "cod_tipo"      => $_POST["nuevaMaquina"],
                    "usureg"        => $usureg,
                    "pcreg"         => $pcreg,
                    "fecreg"        => $fecreg->format("Y-m-d H:i:s")
                );
                ModeloMantenimiento::mdlActualizarEquipoMantProg($datosEquipo);
            } else {
                // Si no es preventivo, actualizar fecha de último mantenimiento en el equipo
                $fechaInicio = new DateTime($_POST["nuevoInicio"]);
                $datosEquipo = array(
                    "fec_ult_mant"  => $fechaInicio->format("Y-m-d"),
                    "cod_tipo"      => $_POST["nuevaMaquina"],
                    "usumod"        => $usureg,
                    "pcmod"         => $pcreg,
                    "fecmod"        => $fecreg->format("Y-m-d H:i:s")
                );
                ModeloMantenimiento::mdlActualizarEquipoMantUlt($datosEquipo);
            }

            // Mostrar mensaje según resultado
            if ($respuesta == "ok") {
                ModeloMantenimiento::mdlActualizarManteTotales($_POST["nuevoId"]);
                echo '<script>
                swal({
                    type: "success",
                    title: "Se creo el mantenimiento",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                }).then(function(result){
                    if (result.value) {
                        window.location = "mantenimiento";
                    }
                })
                </script>';
            } else {
                echo '<script>
                swal({
                    type: "error",
                    title: "No se creo el mantenimiento",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                }).then(function(result){
                    if (result.value) {
                        window.location = "mantenimiento";
                    }
                })
                </script>';
            }
        }
    }

    //*TRAER UBICACION
    static public function ctrTraerUbicacion($valor)
    {

        $respuesta = ModeloMantenimiento::mdlTraerUbicacion($valor);

        return $respuesta;
    }

    //*EDITAR MANTENIMIENTO
    static public function ctrEditarMantenimiento()
    {

        if (isset($_POST["id"])) {

            var_dump($_POST["id"]);

            # traemos la fecha y la pc
            date_default_timezone_set('America/Lima');
            $usumod = $_SESSION["nombre"];
            $pcmod = gethostbyaddr($_SERVER['REMOTE_ADDR']);
            $fecmod = new DateTime();

            $datos = array(
                "id"            => $_POST["id"],
                "mante_inicio"  => $_POST["editarInicio"],
                "mante_fin"     => $_POST["editarFin"],
                "responsable"   => $_POST["editarResponsable"],
                "operario"      => $_POST["editarOperario"],
                "observaciones" => $_POST["editarObservacion"],
                "estado"        => $_POST["editarEstado"],
                "usumod"        => $usumod,
                "pcmod"         => $pcmod,
                "fecmod"        => $fecmod->format("Y-m-d H:i:s")
            );

            var_dump($datos);
            $respuesta = ModeloMantenimiento::mdlEditarMantenimiento($datos);
            var_dump($respuesta);

            $fechaInicio = new DateTime($_POST["editarInicio"]);

            $datosUlt = array(
                "fec_ult_mant"  => $fechaInicio->format("Y-m-d"),
                "cod_tipo"      => $_POST["editarMaquinaCod"],
                "usumod"        => $usumod,
                "pcmod"         => $pcmod,
                "fecmod"        => $fecmod->format("Y-m-d H:i:s")
            );

            #var_dump($datosUlt);
            $respuestaUlt = ModeloMantenimiento::mdlActualizarEquipoMantUlt($datosUlt);
            #var_dump($respuestaUlt);       

            $datosProg = array(
                "fec_ult_mant"  => '0000-00-00 00:00',
                "cod_tipo"      => $_POST["editarMaquinaCod"],
                "usumod"        => $usumod,
                "pcmod"         => $pcmod,
                "fecmod"        => $fecmod->format("Y-m-d H:i:s")
            );

            #var_dump($datosProg);
            $respuestaProg = ModeloMantenimiento::mdlActualizarEquipoMantProg($datosProg);
            #var_dump($respuestaProg);     

            if ($respuesta == "ok") {

                ModeloMantenimiento::mdlActualizarManteTotales($_POST["editarId"]);

                echo '<script>

                swal({
    
                    type: "success",
                    title: "Se edito el mantenimiento",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                    }).then(function(result){
    
                        if (result.value) {
    
                        window.location = "mantenimiento";
    
                        }
    
                    })
    
                </script>';
            } else {

                echo '<script>

                swal({
    
                    type: "error",
                    title: "No se edito el mantenimiento",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                    }).then(function(result){
    
                        if (result.value) {
    
                        window.location = "mantenimiento";
    
                        }
    
                    })
    
                </script>';
            }
        }
    }

    //*CREAR MANTENIMIENTO
    static public function ctrEditarMantenimientoDetalle()
    {

        if (isset($_POST["idD"])) {

            #var_dump($_POST["idD"]);
            # traemos la fecha y la pc
            date_default_timezone_set('America/Lima');
            $usumod = $_SESSION["nombre"];
            $pcmod = gethostbyaddr($_SERVER['REMOTE_ADDR']);
            $fecmod = new DateTime();

            $cantidad = $_POST["editarCantidadD"];
            $precio = $_POST["editarPrecio"];
            $total = $cantidad * $precio;

            $datos = array(
                "id"            => $_POST["idD"],
                "cantidad"      => $cantidad,
                "precio"        => $precio,
                "total"         => $total,
                "observaciones" => $_POST["editarObservacionD"],
                "usumod"        => $usumod,
                "pcmod"         => $pcmod,
                "fecmod"        => $fecmod->format("Y-m-d H:i:s")
            );

            var_dump($datos);
            $respuesta = ModeloMantenimiento::mdlEditarMantenimientoDetalle($datos);
            var_dump($respuesta);

            if ($respuesta == "ok") {

                ModeloMantenimiento::mdlActualizarManteTotales($_POST["editarIdD"]);

                echo '<script>

                swal({
    
                    type: "success",
                    title: "Se edito el detalle",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                    }).then(function(result){
    
                        if (result.value) {
    
                        window.location = "mantenimiento";
    
                        }
    
                    })
    
                </script>';
            } else {

                echo '<script>

                swal({
    
                    type: "error",
                    title: "No se edito el detalle",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                    }).then(function(result){
    
                        if (result.value) {
    
                        window.location = "mantenimiento";
    
                        }
    
                    })
    
                </script>';
            }
        }
    }

    //*ANULAR DETALLE MANTENIMIENTO
    static public function ctrAnularMantenimientoDetalle()
    {

        if (isset($_GET["idDetMante"])) {

            $id = $_GET["idDetMante"];

            $codInt = ModeloMantenimiento::mdlMostrarMantenimientoDetalleEditar($id);

            # traemos la fecha y la pc
            date_default_timezone_set('America/Lima');
            $usuanu = $_SESSION["nombre"];
            $pcanu = gethostbyaddr($_SERVER['REMOTE_ADDR']);
            $fecanu = new DateTime();

            $datos = array(
                "id"        =>  $id,
                "usuanu"    =>  $usuanu,
                "fecanu"    =>  $fecanu->format("Y-m-d H:i:s"),
                "pcanu"     =>  $pcanu
            );

            #var_dump($datos);
            $respuesta = ModeloMantenimiento::mdlAnularMantenimientoDetalle($datos);
            #var_dump($respuesta);     

            if ($respuesta == "ok") {

                ModeloMantenimiento::mdlActualizarManteTotales($codInt["cod_interno"]);

                echo '<script>

                swal({
    
                    type: "success",
                    title: "Se anulo el detalle",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                    }).then(function(result){
    
                        if (result.value) {
    
                        window.location = "mantenimiento";
    
                        }
    
                    })
    
                </script>';
            } else {

                echo '<script>

                swal({
    
                    type: "error",
                    title: "No se anulo el detalle",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                    }).then(function(result){
    
                        if (result.value) {
    
                        window.location = "mantenimiento";
    
                        }
    
                    })
    
                </script>';
            }
        }
    }

    //*MOSTRAR CALENDARIO
    static public function ctrTraerCalendario($valor)
    {

        $respuesta = ModeloMantenimiento::mdlTraerCalendario($valor);

        return $respuesta;
    }

    //*CREAR CALENDARIO
    static public function ctrCrearCalendario()
    {

        if (isset($_POST["nuevoTipo"])) {

            #var_dump($_POST["nuevoTipo"]);
            # traemos la fecha y la pc
            date_default_timezone_set('America/Lima');
            $usureg = $_SESSION["nombre"];
            $pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);
            $fecreg = new DateTime();

            $_POST["nuevaObservacion"] = preg_replace("/[\r\n|\n|\r]+/", " ", $_POST["nuevaObservacion"]);

            $datos = array(
                "tipo"          => $_POST["nuevoTipo"],
                "titulo"        => $_POST["nuevoTitulo"],
                "cod_interno"   => '',
                "inicio"        => $_POST["nuevoInicio"],
                "fin"           => $_POST["nuevoFin"],
                "allday"        => '',
                "dirurl"        => '',
                "indicaciones"  => $_POST["nuevaObservacion"],
                "estado"        => 'Pendiente',
                "usureg"        => $usureg,
                "pcreg"         => $pcreg,
                "fecreg"        => $fecreg->format("Y-m-d H:i:s")
            );

            #var_dump($datos);
            $respuesta = ModeloMantenimiento::mdlCrearCalendario($datos);
            #var_dump($respuesta);   

            if ($respuesta == "ok") {

                ModeloMantenimiento::mdlActualizarManteTotales($_POST["nuevoId"]);

                echo '<script>

                swal({
    
                    type: "success",
                    title: "Se creo la actividad",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                    }).then(function(result){
    
                        if (result.value) {
    
                        window.location = "calendario";
    
                        }
    
                    })
    
                </script>';
            } else {

                echo '<script>

                swal({
    
                    type: "error",
                    title: "No se creo la actividad",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                    }).then(function(result){
    
                        if (result.value) {
    
                        window.location = "calendario";
    
                        }
    
                    })
    
                </script>';
            }
        }
    }

    //*CREAR CALENDARIO
    static public function ctrEditarCalendario()
    {

        if (isset($_POST["id"])) {

            var_dump($_POST["id"]);
            # traemos la fecha y la pc
            date_default_timezone_set('America/Lima');
            $usumod = $_SESSION["nombre"];
            $pcmod = gethostbyaddr($_SERVER['REMOTE_ADDR']);
            $fecmod = new DateTime();

            $_POST["editarObservacion"] = preg_replace("/[\r\n|\n|\r]+/", " ", $_POST["editarObservacion"]);

            $datos = array(
                "id"            => $_POST["id"],
                "tipo"          => $_POST["editarTipo"],
                "titulo"        => $_POST["editarTitulo"],
                "cod_interno"   => '',
                "inicio"        => $_POST["editarInicio"],
                "fin"           => $_POST["editarFin"],
                "allday"        => '',
                "dirurl"        => '',
                "indicaciones"  => $_POST["editarObservacion"],
                "estado"        => 'Pendiente',
                "usumod"        => $usumod,
                "pcmod"         => $pcmod,
                "fecmod"        => $fecmod->format("Y-m-d H:i:s")
            );

            #var_dump($datos); 
            $respuesta = ModeloMantenimiento::mdlEditarCalendario($datos);
            #var_dump($respuesta);                    

            if ($respuesta == "ok") {

                ModeloMantenimiento::mdlActualizarManteTotales($_POST["nuevoId"]);

                echo '<script>

                swal({
    
                    type: "success",
                    title: "Se creo la actividad",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                    }).then(function(result){
    
                        if (result.value) {
    
                        window.location = "calendario";
    
                        }
    
                    })
    
                </script>';
            } else {

                echo '<script>

                swal({
    
                    type: "error",
                    title: "No se creo la actividad",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                    }).then(function(result){
    
                        if (result.value) {
    
                        window.location = "calendario";
    
                        }
    
                    })
    
                </script>';
            }
        }
    }

    //*ANULAR CALNDARIO
    static public function ctrAnularCalendario()
    {

        if (isset($_GET["idCalendario"])) {

            $id = $_GET["idCalendario"];

            # traemos la fecha y la pc
            date_default_timezone_set('America/Lima');
            $usuanu = $_SESSION["nombre"];
            $pcanu = gethostbyaddr($_SERVER['REMOTE_ADDR']);
            $fecanu = new DateTime();

            $datos = array(
                "id"        =>  $id,
                "usuanu"    =>  $usuanu,
                "fecanu"    =>  $fecanu->format("Y-m-d H:i:s"),
                "pcanu"     =>  $pcanu
            );

            #var_dump($datos);
            $respuesta = ModeloMantenimiento::mdlAnularCalendario($datos);
            #var_dump($respuesta);      

            if ($respuesta == "ok") {

                echo '<script>

                swal({
    
                    type: "success",
                    title: "Se anulo la actividad",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                    }).then(function(result){
    
                        if (result.value) {
    
                        window.location = "calendario";
    
                        }
    
                    })
    
                </script>';
            } else {

                echo '<script>

                swal({
    
                    type: "error",
                    title: "No se anulo la actividad",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                    }).then(function(result){
    
                        if (result.value) {
    
                        window.location = "calendario";
    
                        }
    
                    })
    
                </script>';
            }
        }
    }
}
