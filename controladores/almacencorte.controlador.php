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
    *
    * Los detalles NO se borran y reinsertan: se actualizan por id para conservar saldo_taller
    * (lo ya enviado a taller/servicios) y las referencias almacencorte_detalle_id de los movimientos.
    * Todo corre en una transacción: si algo falla no queda el corte a medias.
    */
    static public function ctrEditarAlmacenCorte()
    {

        if (
            !isset($_POST["editarAlmacenCorte"]) ||
            !isset($_POST["idUsuario"]) ||
            !isset($_POST["listaArticulosAC"])
        ) {
            return;
        }

        $codigo = (int) $_POST["editarAlmacenCorte"];
        $rutaEditar = "index.php?ruta=editar-almacencorte&codigo=" . $codigo;

        if ($_POST["listaArticulosAC"] == "") {
            self::alertaAlmacenCorte("error", "Error", "¡No se realizo ningún cambio. Por favor, intenteló de nuevo!", $rutaEditar);
            return;
        }

        $listaArticulosAC = json_decode($_POST["listaArticulosAC"], true);

        if (!is_array($listaArticulosAC) || count($listaArticulosAC) == 0) {
            self::alertaAlmacenCorte("error", "Error", "¡Debe quedar al menos un artículo en el corte!", $rutaEditar);
            return;
        }

        $guia = isset($_POST["editarGuia"]) ? trim((string) $_POST["editarGuia"]) : "";

        if ($guia === "") {
            self::alertaAlmacenCorte("error", "Error", "¡Debe ingresar la guía de corte!", $rutaEditar);
            return;
        }

        if (ModeloAlmacenCorte::mdlExisteGuiaOtroAC($guia, $codigo)) {
            self::alertaAlmacenCorte("error", "Guía duplicada", "¡Esa guía ya fue registrada en otro corte!", $rutaEditar);
            return;
        }

        // Detalles actuales del corte, indexados por detalle de orden de corte
        $detallesActuales = ModeloAlmacenCorte::mdlMostarDetallesAlmacenCorte("almacencorte_detallejf", "almacencorte", $codigo);
        $actualesPorDet = [];
        foreach ($detallesActuales as $d) {
            $actualesPorDet[(int) $d["detordencorte"]] = $d;
        }

        // Validar lo nuevo contra lo ya enviado a taller/servicios (cantidad - saldo_taller)
        $nuevosPorDet = [];
        foreach ($listaArticulosAC as $fila) {

            $idocd = (int) $fila["idocd"];
            $cantidadNueva = (int) $fila["cantidad"];

            if ($cantidadNueva <= 0 || isset($nuevosPorDet[$idocd])) {
                self::alertaAlmacenCorte("error", "Error", "¡Hay cantidades inválidas o líneas repetidas en el corte!", $rutaEditar);
                return;
            }

            $nuevosPorDet[$idocd] = $fila;

            if (isset($actualesPorDet[$idocd])) {

                $act = $actualesPorDet[$idocd];
                $saldoTaller = $act["saldo_taller"] === null ? (int) $act["cantidad"] : (int) $act["saldo_taller"];
                $enviado = (int) $act["cantidad"] - $saldoTaller;

                if ($cantidadNueva < $enviado) {
                    self::alertaAlmacenCorte(
                        "error",
                        "No se puede reducir",
                        "¡El artículo " . $act["articulo"] . " ya tiene " . $enviado . " unidades enviadas a taller/servicio. La cantidad no puede ser menor!",
                        $rutaEditar
                    );
                    return;
                }
            }
        }

        foreach ($actualesPorDet as $idocd => $act) {

            if (!isset($nuevosPorDet[$idocd])) {

                $saldoTaller = $act["saldo_taller"] === null ? (int) $act["cantidad"] : (int) $act["saldo_taller"];

                if ((int) $act["cantidad"] - $saldoTaller > 0) {
                    self::alertaAlmacenCorte(
                        "error",
                        "No se puede quitar",
                        "¡El artículo " . $act["articulo"] . " ya tiene unidades enviadas a taller/servicio y no se puede quitar del corte!",
                        $rutaEditar
                    );
                    return;
                }
            }
        }

        // Totales por artículo (varias órdenes de corte pueden traer el mismo artículo)
        $totales = [];
        foreach ($listaArticulosAC as $fila) {
            $art = $fila["articulo"];
            $totales[$art] = (isset($totales[$art]) ? $totales[$art] : 0) + (int) $fila["cantidad"];
        }

        $pdo = Conexion::conectar();
        $modoAnterior = $pdo->getAttribute(PDO::ATTR_ERRMODE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        try {

            $pdo->beginTransaction();

            // 1. Recuperar todo como antes del corte (articulo y saldos de orden de corte)
            foreach ($detallesActuales as $d) {

                ModeloAlmacenCorte::mdlRecuperarAlmCorte($d["articulo"], $d["cantidad"]);

                ModeloAlmacenCorte::mdlRecuperarOrdCorte($d["articulo"], $d["cantidad"]);

                ModeloAlmacenCorte::mdlRecuperarSaldoOrdCorte($d["articulo"], $d["ordencorte"], $d["cantidad"]);
            }

            // 2. Aplicar los totales nuevos por artículo
            foreach ($totales as $articulo => $cantidad) {

                ModeloAlmacenCorte::mdlActualizarAlmCorte($articulo, $cantidad);

                ModeloAlmacenCorte::mdlActualizarOrdCorte($articulo, $cantidad);
            }

            // 3. Saldos de detalles de orden de corte
            foreach ($listaArticulosAC as $fila) {

                ModeloAlmacenCorte::mdlActualizarSaldoOrdCorte($fila["articulo"], $fila["ordencorte"], $fila["cantidad"]);
            }

            ModeloAlmacenCorte::mdlActualizarOrdCorteSaldo();

            ModeloAlmacenCorte::mdlActualizarSaldoOrdCorteGral();

            ModeloAlmacenCorte::mdlActualizarOrdCorteEstadoParcial();

            ModeloAlmacenCorte::mdlActualizarOrdCorteEstadoCerrado();

            // 4. Cabecera
            $datos = array(
                "codigo" => $codigo,
                "guia" => $guia,
                "usuario" => $_POST["idUsuario"],
                "total" => $_POST["totalAlmacenCorte"],
                "estado" => "1"
            );

            if (ModeloAlmacenCorte::mdlEditarAlmacenCorte($datos) != "ok") {
                throw new Exception("No se pudo actualizar la cabecera del corte");
            }

            // 5. Detalles: actualizar por id (conserva saldo_taller), quitar los removidos, insertar los nuevos
            foreach ($nuevosPorDet as $idocd => $fila) {

                $cantidadNueva = (int) $fila["cantidad"];

                if (isset($actualesPorDet[$idocd])) {

                    $act = $actualesPorDet[$idocd];
                    $diferencia = $cantidadNueva - (int) $act["cantidad"];

                    if ($diferencia != 0 || $act["saldo_taller"] === null) {
                        ModeloAlmacenCorte::mdlActualizarDetalleAlmacenCorte($act["id"], $cantidadNueva, $diferencia);
                    }
                } else {

                    $datosD = array(
                        "almacencorte" => $codigo,
                        "ordcorte" => $fila["ordencorte"],
                        "idocd" => $idocd,
                        "articulo" => $fila["articulo"],
                        "cantidad" => $cantidadNueva
                    );

                    if (ModeloAlmacenCorte::mdlGuardarDetallesAlmacenCorte($datosD) != "ok") {
                        throw new Exception("No se pudo guardar un detalle del corte");
                    }
                }
            }

            foreach ($actualesPorDet as $idocd => $act) {

                if (!isset($nuevosPorDet[$idocd])) {
                    ModeloAlmacenCorte::mdlEliminarDetalleAlmacenCorte($act["id"]);
                }
            }

            // 6. Consumo de materia prima del corte: se regenera desde los detalles ya actualizados
            ModeloIngresos::mdlEliminarDato("almacencorte_detalle_mpjf", "almacencorte", $codigo);

            ModeloAlmacenCorte::mdlGuardarDetallesAlmacenCorteMP($codigo);

            $pdo->commit();

            $pdo->setAttribute(PDO::ATTR_ERRMODE, $modoAnterior);

            self::alertaAlmacenCorte("success", "Felicitaciones", "¡La información fue editada con éxito!", "almacencorte");
        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $pdo->setAttribute(PDO::ATTR_ERRMODE, $modoAnterior);

            error_log("ctrEditarAlmacenCorte {$codigo}: " . $e->getMessage());

            self::alertaAlmacenCorte(
                "error",
                "Error",
                "¡La información presento problemas y no se registro. No se hizo ningún cambio, por favor intenteló de nuevo!",
                $rutaEditar
            );
        }
    }
}
