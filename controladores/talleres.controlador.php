<?php
// session_start();
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

class ControladorTalleres
{

    /*
    * MOSTRAR DATOS DE TALLERES GENERAL
    */
    static public function ctrMostrarTalleresG($valor)
    {

        $respuesta = ModeloTalleres::mdlMostrarTalleresG($valor);

        return $respuesta;
    }

    static public function ctrEnTalleres($taller)
    {
        $respuesta = ModeloTalleres::mdlEnTalleres($taller);

        return $respuesta;
    }

    /*
    * MOSTRAR DATOS DE TALLERES TERMINADO GENERAL
    */
    static public function ctrMostrarTalleresTerminado($valor)
    {

        $respuesta = ModeloTalleres::mdlMostrarTalleresTerminado($valor);

        return $respuesta;
    }

    /*
    * MOSTRAR DATOS DE TALLERES GENERADOS GENERAL
    */
    static public function ctrMostrarTalleresGenerados($articuloTaller)
    {

        $respuesta = ModeloTalleres::mdlMostrarTalleresGenerados($articuloTaller);

        return $respuesta;
    }
    /*
    * MOSTRAR DATOS DE TALLERES PROCESO 5 LINEAS
    */
    static public function ctrMostrarTalleresP()
    {

        $respuesta = ModeloTalleres::mdlMostrarTalleresP();

        return $respuesta;
    }

    /*
    * MOSTRAR DATOS DE TALLERES TERMINADO
    */
    static public function ctrMostrarTalleresT()
    {

        $respuesta = ModeloTalleres::mdlMostrarTalleresT();

        return $respuesta;
    }

    /*
    * MOSTRAR DATOS DE TALLERES GENERAL
    */
    static public function ctrMostrarTallerCabecera($item, $valor)
    {

        $tabla = "entaller_cabjf";
        $respuesta = ModeloTalleres::mdlMostrarTallerCabecera($tabla, $item, $valor);

        return $respuesta;
    }

    /*
    * MOSTRAR DATOS DE TALLERES TERMINADO GENERAL
    */
    static public function ctrActualizarTallerT($valor1, $valor2)
    {

        $respuesta = ModeloTalleres::mdlActualizarTallerT($valor1, $valor2);

        return $respuesta;
    }

    /* 
    * ACTUALIZAR A EN PROCESO
    */
    static public function ctrProceso()
    {

        if (isset($_POST["codigoBarra"])) {

            $cobar = $_POST["codigoBarra"];

            $validar = ModeloTalleres::mdlMostrarTalleresG($cobar);
            var_dump($validar);
            var_dump("fecha_proceso", $validar["fecha_proceso"]);

            if ($validar["fecha_proceso"] == null) {

                # Actualizamos ultima_compra en la tabla Clientes
                date_default_timezone_set('America/Lima');

                //$fecha = "2021-02-26";
                $fecha = date('Y-m-d G:i:s');
                //var_dump($fecha);

                $codigo = $_POST["codigoBarra"];
                $trabajador = $_POST["cod_tra"];


                $respuesta = ModeloTalleres::mdlProceso($fecha, $codigo, $trabajador);
                //var_dump($respuesta);

                $respuesta2 = ModeloTalleres::mdlTerminado($fecha, $codigo, $trabajador);

                if ($respuesta == "ok") {

                    echo '<script>
                Command: toastr["success"]("Registrado exitosamente!");

                </script>';
                } else {
                    echo '<script>
                        Command: toastr["error"]("El ticket ya fue registrado antes!");

                        </script>';
                }
            } else {

                # Actualizamos ultima_compra en la tabla Clientes
                date_default_timezone_set('America/Lima');

                //$fecha = "2021-02-26";
                $fecha = date('Y-m-d G:i:s');
                //var_dump($fecha);

                $codigo = $_POST["codigoBarra"];
                $trabajador = $_POST["cod_tra"];

                $respuesta = ModeloTalleres::mdlProceso($fecha, $codigo, $trabajador);

                $respuesta = ModeloTalleres::mdlTerminado($fecha, $codigo, $trabajador);

                if ($respuesta == "ok") {
                    echo '<script>
                        
                        Command: toastr["success"]("Registrado exitosamente!");
                        

                        </script>';
                } else {
                    echo '<script>
                        Command: toastr["error"]("El ticket ya fue registrado antes!");

                        </script>';
                }
            }
        }
    }

    static public function ctrProcesoB()
    {

        if (isset($_POST["codigoBarra"])) {

            //var_dump($_POST["codigoBarra"]);

            $cobar = $_POST["codigoBarra"];
            $validar = ModeloTalleres::mdlMostrarTalleresG($cobar);
            //var_dump("fecha_proceso", $validar["fecha_proceso"]);

            if ($validar["fecha_proceso"] == null) {

                date_default_timezone_set('America/Lima');

                //$fecha = "2021-02-26";
                $fecha = date('Y-m-d G:i:s');
                //var_dump($fecha);

                $codigo = $_POST["codigoBarra"];
                $trabajador = $_POST["cod_tra"];

                $respuesta = ModeloTalleres::mdlProceso($fecha, $codigo, $trabajador);
                //var_dump($respuesta);

                $respuesta2 = ModeloTalleres::mdlTerminado($fecha, $codigo, $trabajador);

                echo '<script>
                    Command: toastr["success"]("Registrado exitosamente!");
    
                    </script>';
            } else {

                echo '<script>
                Command: toastr["error"]("El ticket ya fue registrado antes!");

                </script>';
            }
        }
    }


    /* 
	* Asignar codigo de barra a trabajador
	*/

    static public function ctrAsignarTrabajador()
    {

        if (isset($_POST["cod_tra"])) {
            $datos = array(
                "codigo" => $_POST["codigoBarra"],
                "trabajador" => $_POST["cod_tra"],
                "fecha_proceso" => $_POST["editarFechaProceso"],
                "fecha_terminado" => $_POST["editarFechaTerminado"]
            );

            $respuesta = ModeloTalleres::mdlAsignarTrabajador($datos);
            // var_dump($respuesta);

            if ($respuesta == "ok") {

                echo '<script>
                    swal({
						  type: "success",
						  title: "El trabajador y fecha ha sido cambiada correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "en-tallert";

									}
								})

					</script>';
            }
        }
    }

    /*=============================================
	Dividir Cantidad Taller General
	=============================================*/

    static public function ctrEditarCantidad()
    {

        if (isset($_POST["editarCantidad2"])) {
            $dividir = substr($_POST["editarBarra"], -1);
            $cantidad = strlen($_POST["editarBarra"]);
            if ($cantidad == 11) {
                if ($dividir == 'A') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperacion"] . "2";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == 'B') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperacion"] . "3";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTa ller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == 'C') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperacion"] . "4";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == 'D') {

                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperacion"] . "5";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == 'E') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperacion"] . "6";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == 'F') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperacion"] . "7";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == '1') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperacion"] . "2";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == '2') {

                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperacion"] . "3";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == '3') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperacion"] . "4";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == '4') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperacion"] . "5";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == '5') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperacion"] . "6";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == '6') {

                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperacion"] . "7";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == '7') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperacion"] . "8";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == '8') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperacion"] . "9";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                }
            } else {
                $datos = array(
                    "codigo" => $_POST["editarCodigo"],
                    "usuario" => $_POST["usuario"],
                    "articulo" => $_POST["editarArticulo"],
                    "operacion" => $_POST["editarCodOperacion"],
                    "cantidad" => $_POST["editarCantidad2"],
                    "editarBarra" => $_POST["editarBarra"]
                );

                $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperacion"] . "1";
                $datos2 = array(
                    "codigo" => $_POST["editarCodigo"],
                    "usuario" => $_POST["usuario"],
                    "articulo" => $_POST["editarArticulo"],
                    "operacion" => $_POST["editarCodOperacion"],
                    "cantidad" => $cantidad2,
                    "editarBarra" => $codigoBarraNuevo
                );
                $tabla = "entallerjf";
                $id = $_POST["editarTaller"];
                $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                $respuesta = ModeloTalleres::mdlIngresarTaller($datos2);
            }


            if ($respuesta == "ok") {
                $ultimo = $_POST["editarBarra"];
                $valor = $_POST["editarArticulo"];
                $rpt_articulo = ModeloArticulos::mdlMostrarArticulos($valor);
                $modelo = $rpt_articulo["modelo"];
                $nombre = $rpt_articulo["nombre"];
                $color = $rpt_articulo["color"];
                $talla = $rpt_articulo["talla"];
                $cantidad = $_POST["editarCantidad2"];
                $cod_ope = $_POST["editarCodOperacion"];
                $tablaop = "operacionesjf";
                $itemop = "codigo";
                $rpt_operacion = ModeloOperaciones::mdlMostrarOperaciones($tablaop, $itemop, $cod_ope);
                $nom_ope = $rpt_operacion["nombre"];

                // $ultimo2 = $codigoBarraNuevo;

                echo '<script>
                    
                    window.open("vistas/reportes_ticket/produccion_ticket_dividir2.php?ultimo=' . $ultimo . '&modelo=' . $modelo . '&nombre=' . $nombre . '&color=' . $color . '&talla=' . $talla . '&cant_taller=' . $cantidad . '&cod_operacion=' . $cod_ope . '&nom_operacion=' . $nom_ope . '","_blank");
                    </script>';
                echo '<script>

					swal({
						  type: "success",
						  title: "La cantidad ha sido cambiada correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "en-taller";

									}
								})

					</script>';
            }
        }
    }


    /*=============================================
	RANGO FECHAS
	=============================================*/

    static public function ctrRangoFechasTalleres($fechaInicial, $fechaFinal)
    {

        $tabla = "entallerjf";

        $respuesta = ModeloTalleres::mdlRangoFechasTalleres($tabla, $fechaInicial, $fechaFinal);

        return $respuesta;
    }

    /*=============================================
	RANGO FECHAS
	=============================================*/

    static public function ctrRangoFechasTalleresOperaciones($modelo)
    {


        $respuesta = ModeloTalleres::mdlRangoFechasTalleresOperaciones($modelo);

        return $respuesta;
    }


    static public function ctrMes()
    {

        $respuesta = ModeloTalleres::mdlMes();

        return $respuesta;
    }


    static public function ctrMesB($mes)
    {

        $respuesta = ModeloTalleres::mdlMesB($mes);

        return $respuesta;
    }

    /*
    * MOSTRAR PRODUCCION DE TRUSAS
    */
    static public function ctrMostrarProduccionTrusas($fechaInicial, $fechaFinal)
    {

        $respuesta = ModeloTalleres::mdlMostrarProduccionTrusas($fechaInicial, $fechaFinal);

        return $respuesta;
    }

    /*
    * MOSTRAR PRODUCCION DE BRASIER
    */
    static public function ctrMostrarProduccionBrasier($fechaInicial, $fechaFinal)
    {

        $respuesta = ModeloTalleres::mdlMostrarProduccionBrasier($fechaInicial, $fechaFinal);

        return $respuesta;
    }

    /*
    * MOSTRAR PRODUCCION DE VASCO
    */
    static public function ctrMostrarProduccionVasco($mes)
    {

        $respuesta = ModeloTalleres::mdlMostrarProduccionVasco($mes);

        return $respuesta;
    }


    /*=============================================
	RANGO FECHAS TERMINADOS
	=============================================*/

    static public function ctrRangoFechasTalleresTerminados($fechaInicial, $fechaFinal)
    {

        $tabla = "entallerjf";

        $respuesta = ModeloTalleres::mdlRangoFechasTalleresTerminados($tabla, $fechaInicial, $fechaFinal);

        return $respuesta;
    }


    /*=============================================
	Dividir Cantidad Taller Terminado
	=============================================*/

    static public function ctrEditarCantidadTerminado()
    {

        if (isset($_POST["editarCantidades"])) {
            $dividir = substr($_POST["editarBarra"], -1);
            $cantidad = strlen($_POST["editarBarra"]);
            if ($cantidad == 11) {
                if ($dividir == 'A') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"],
                        "trabajador" => $_POST["trabajador"],
                        "fecha_proceso" => $_POST["fecha_proceso"],
                        "fecha_terminado" => $_POST["fecha_terminado"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "2";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTallerTerminado($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == 'B') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"],
                        "trabajador" => $_POST["trabajador"],
                        "fecha_proceso" => $_POST["fecha_proceso"],
                        "fecha_terminado" => $_POST["fecha_terminado"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "3";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTallerTerminado($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == 'C') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"],
                        "trabajador" => $_POST["trabajador"],
                        "fecha_proceso" => $_POST["fecha_proceso"],
                        "fecha_terminado" => $_POST["fecha_terminado"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "4";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTallerTerminado($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == 'D') {

                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"],
                        "trabajador" => $_POST["trabajador"],
                        "fecha_proceso" => $_POST["fecha_proceso"],
                        "fecha_terminado" => $_POST["fecha_terminado"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "5";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTallerTerminado($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == 'E') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"],
                        "trabajador" => $_POST["trabajador"],
                        "fecha_proceso" => $_POST["fecha_proceso"],
                        "fecha_terminado" => $_POST["fecha_terminado"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "6";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTallerTerminado($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == 'F') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"],
                        "trabajador" => $_POST["trabajador"],
                        "fecha_proceso" => $_POST["fecha_proceso"],
                        "fecha_terminado" => $_POST["fecha_terminado"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "7";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTallerTerminado($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == '1') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"],
                        "trabajador" => $_POST["trabajador"],
                        "fecha_proceso" => $_POST["fecha_proceso"],
                        "fecha_terminado" => $_POST["fecha_terminado"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "2";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTallerTerminado($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == '2') {

                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"],
                        "trabajador" => $_POST["trabajador"],
                        "fecha_proceso" => $_POST["fecha_proceso"],
                        "fecha_terminado" => $_POST["fecha_terminado"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "3";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTallerTerminado($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == '3') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"],
                        "trabajador" => $_POST["trabajador"],
                        "fecha_proceso" => $_POST["fecha_proceso"],
                        "fecha_terminado" => $_POST["fecha_terminado"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "4";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTallerTerminado($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == '4') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"],
                        "trabajador" => $_POST["trabajador"],
                        "fecha_proceso" => $_POST["fecha_proceso"],
                        "fecha_terminado" => $_POST["fecha_terminado"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "5";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTallerTerminado($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == '5') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"],
                        "trabajador" => $_POST["trabajador"],
                        "fecha_proceso" => $_POST["fecha_proceso"],
                        "fecha_terminado" => $_POST["fecha_terminado"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "6";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTallerTerminado($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == '6') {

                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"],
                        "trabajador" => $_POST["trabajador"],
                        "fecha_proceso" => $_POST["fecha_proceso"],
                        "fecha_terminado" => $_POST["fecha_terminado"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "7";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTallerTerminado($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == '7') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"],
                        "trabajador" => $_POST["trabajador"],
                        "fecha_proceso" => $_POST["fecha_proceso"],
                        "fecha_terminado" => $_POST["fecha_terminado"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "8";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTallerTerminado($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == '8') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"],
                        "trabajador" => $_POST["trabajador"],
                        "fecha_proceso" => $_POST["fecha_proceso"],
                        "fecha_terminado" => $_POST["fecha_terminado"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "9";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTallerTerminado($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                }
            } else {
                $datos = array(
                    "codigo" => $_POST["editarCodigo"],
                    "usuario" => $_POST["usuario"],
                    "articulo" => $_POST["editarArticulo"],
                    "operacion" => $_POST["editarCodOperaciones"],
                    "cantidad" => $_POST["editarCantidades"],
                    "editarBarra" => $_POST["editarBarra"],
                    "trabajador" => $_POST["trabajador"],
                    "fecha_proceso" => $_POST["fecha_proceso"],
                    "fecha_terminado" => $_POST["fecha_terminado"]
                );

                $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "1";
                $datos2 = array(
                    "codigo" => $_POST["editarCodigo"],
                    "usuario" => $_POST["usuario"],
                    "articulo" => $_POST["editarArticulo"],
                    "operacion" => $_POST["editarCodOperaciones"],
                    "cantidad" => $cantidad2,
                    "editarBarra" => $codigoBarraNuevo
                );
                $tabla = "entallerjf";
                $id = $_POST["editarTaller"];
                $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                $respuesta = ModeloTalleres::mdlIngresarTallerTerminado($datos);
                $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
            }

            if ($respuesta == "ok" && $respuesta2 == "ok") {
                $ultimo = $codigoBarraNuevo;
                $valor = $_POST["editarArticulo"];
                $rpt_articulo = ModeloArticulos::mdlMostrarArticulos($valor);
                $modelo = $rpt_articulo["modelo"];
                $nombre = $rpt_articulo["nombre"];
                $color = $rpt_articulo["color"];
                $talla = $rpt_articulo["talla"];
                $cantidad = $cantidad2;
                $cod_ope = $_POST["editarCodOperaciones"];
                $tablaop = "operacionesjf";
                $itemop = "codigo";
                $rpt_operacion = ModeloOperaciones::mdlMostrarOperaciones($tablaop, $itemop, $cod_ope);
                $nom_ope = $rpt_operacion["nombre"];

                // $ultimo2 = $codigoBarraNuevo;

                echo '<script>
                    
                    window.open("vistas/reportes_ticket/produccion_ticket_dividir2.php?ultimo=' . $ultimo . '&modelo=' . $modelo . '&nombre=' . $nombre . '&color=' . $color . '&talla=' . $talla . '&cant_taller=' . $cantidad . '&cod_operacion=' . $cod_ope . '&nom_operacion=' . $nom_ope . '","_blank");
                    </script>';
                echo '<script>

					swal({
						  type: "success",
						  title: "La cantidad ha sido cambiada correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "en-tallert";

									}
								})

					</script>';
            }
        }
    }

    /*=============================================
	EXPORTAR TICKET POR CODIGO UNICO TALLER CABECERA
	=============================================*/

    static public function ctrExportarArticulo()
    {

        if (isset($_POST["nuevoCodigo"])) {

            $cod = $_POST["nuevoCodigo"];

            $nombre_impresora = "Star BSC10";

            $connector = new WindowsPrintConnector($nombre_impresora);
            $printer = new Printer($connector);

            $fecha = date("d-m-Y");

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


            echo '<script>

            swal({
                  type: "success",
                  title: "Se exporto ticket de articulo a taller correctamente",
                  showConfirmButton: true,
                  confirmButtonText: "Cerrar"
                  }).then(function(result){
                            if (result.value) {

                            window.location = "en-taller";

                            }
                        })

            </script>';
        }
    }

    /*=============================================
	ELIMINAR TALLER POR CODIGO UNICO TALLER CABECERA
	=============================================*/

    static public function ctrEliminarArticulo()
    {

        if (isset($_POST["nuevoCodigo2"])) {
            $tabla = "entallerjf";
            $cod = $_POST["nuevoCodigo2"];
            $tabla2 = "entaller_cabjf";
            
            // 1. Traer la cabecera taller ANTES de eliminar cualquier dato
            $cabeceraTaller = ControladorTalleres::ctrMostrarTallerCabecera("id", $cod);
            
            if (!$cabeceraTaller) {
                echo '<script>
                swal({
                    type: "error",
                    title: "Error",
                    text: "No se encontró el bloque de taller",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                })
                </script>';
                return;
            }

            // 2. Sincronizar almacencorte_detallejf (revertir saldo_taller)
            // El modelo cortes.modelo.php ya está cargado desde index.php
            $respuestaSync = ModeloCortes::mdlEliminarBloqueTaller($cod);
            
            if ($respuestaSync != "ok") {
                echo '<script>
                swal({
                    type: "error",
                    title: "Error",
                    text: "Error al sincronizar almacencorte_detallejf: ' . $respuestaSync . '",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                })
                </script>';
                return;
            }

            // 3. Actualizar articulojf (revertir taller/servicio y aumentar alm_corte)
            $existeServicio = ControladorServicios::ctrMostrarDetallesServicios("cabecera_taller", $cod);

            if ($existeServicio) {
                /* 
                * Actualizamos la cantidad que quedo en servicio y regresa al corte
                */
                $articulo  = $cabeceraTaller["articulo"];
                $cantidad =  $cabeceraTaller["cantidad"];

                $respuesta = ModeloArticulos::mdlActualizarServicioEliminado($articulo, $cantidad);

                /* 
                * Eliminar el servicio detalle 
                */
                $eliminarDeta = ModeloServicios::mdlEliminarDato("servicios_detallejf", "cabecera_taller", $cod);
            } else {
                /* 
                * Actualizamos la cantidad que quedo en taller y regresa al corte
                */
                $articulo  = $cabeceraTaller["articulo"];
                $cantidad =  $cabeceraTaller["cantidad"];

                $respuesta = ModeloArticulos::mdlActualizarTallerEliminado($articulo, $cantidad);
            }

            // 4. Eliminar detalles (entallerjf)
            $respuesta1 = ModeloTalleres::mdlEliminarTallerDetalle($tabla, $cod);
            
            // 5. Eliminar detalle cab (si existe)
            $respuestaB = ModeloTalleres::mdlEliminarTallerDetalleCab($cod);
            
            // 6. Finalmente eliminar la cabecera (entaller_cabjf)
            $respuesta2 = ModeloTalleres::mdlEliminarTaller($tabla2, $cod);
            if ($respuesta2 == "ok") {
                echo '<script>

                swal({
                    type: "success",
                    title: "Se elimino el ticket de taller correctamente",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                    }).then(function(result){
                                if (result.value) {

                                window.location = "en-taller";

                                }
                            })
                </script>';
            }
        }
    }

    /*=============================================
	Dividir cantidad Taller Operaciones
	=============================================*/

    static public function ctrEditarCantidadOperacion()
    {

        if (isset($_POST["editarCantidad2"])) {
            $dividir = substr($_POST["editarBarra"], -1);
            $cantidad = strlen($_POST["editarBarra"]);
            if ($cantidad == 11) {
                if ($dividir == 'A') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarBarra"] . "2";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == 'B') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarBarra"] . "3";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == 'C') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarBarra"] . "4";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == 'D') {

                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarBarra"] . "5";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == 'E') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarBarra"] . "6";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == 'F') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarBarra"] . "7";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == '1') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarBarra"] . "2";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == '2') {

                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarBarra"] . "3";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == '3') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarBarra"] . "4";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == '4') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarBarra"] . "5";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == '5') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarBarra"] . "6";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == '6') {

                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarBarra"] . "7";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == '7') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarBarra"] . "8";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } else if ($dividir == '8') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $_POST["editarCantidad2"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                    $codigoBarraNuevo = $_POST["editarBarra"] . "9";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperacion"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                }
            } else {
                $datos = array(
                    "codigo" => $_POST["editarCodigo"],
                    "usuario" => $_POST["usuario"],
                    "articulo" => $_POST["editarArticulo"],
                    "operacion" => $_POST["editarCodOperacion"],
                    "cantidad" => $_POST["editarCantidad2"],
                    "editarBarra" => $_POST["editarBarra"]
                );

                $cantidad2 = $_POST["cantidad"] - $_POST["editarCantidad2"];
                $codigoBarraNuevo = $_POST["editarBarra"] . "1";
                $datos2 = array(
                    "codigo" => $_POST["editarCodigo"],
                    "usuario" => $_POST["usuario"],
                    "articulo" => $_POST["editarArticulo"],
                    "operacion" => $_POST["editarCodOperacion"],
                    "cantidad" => $cantidad2,
                    "editarBarra" => $codigoBarraNuevo
                );
                $tabla = "entallerjf";
                $id = $_POST["editarTaller"];
                $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                $respuesta = ModeloTalleres::mdlIngresarTaller($datos2);
            }


            if ($respuesta == "ok") {
                $ultimo = $_POST["editarBarra"];
                $valor = $_POST["editarArticulo"];
                $rpt_articulo = ModeloArticulos::mdlMostrarArticulos($valor);
                $modelo = $rpt_articulo["modelo"];
                $nombre = $rpt_articulo["nombre"];
                $color = $rpt_articulo["color"];
                $talla = $rpt_articulo["talla"];
                $cantidad = $_POST["editarCantidad2"];
                $cod_ope = $_POST["editarCodOperacion"];
                $tablaop = "operacionesjf";
                $itemop = "codigo";
                $rpt_operacion = ModeloOperaciones::mdlMostrarOperaciones($tablaop, $itemop, $cod_ope);
                $nom_ope = $rpt_operacion["nombre"];

                // $ultimo2 = $codigoBarraNuevo;

                echo '<script>
                    
                    window.open("vistas/reportes_ticket/produccion_ticket_dividir2.php?ultimo=' . $ultimo . '&modelo=' . $modelo . '&nombre=' . $nombre . '&color=' . $color . '&talla=' . $talla . '&cant_taller=' . $cantidad . '&cod_operacion=' . $cod_ope . '&nom_operacion=' . $nom_ope . '","_blank");
                    </script>';
                echo '<script>

					swal({
						  type: "success",
						  title: "La cantidad ha sido cambiada correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "operacion-taller";

									}
								})

					</script>';
            }
        }
    }

    /*=============================================
	Dividir Cantidad Taller Generado
	=============================================*/

    static public function ctrEditarCantidadGenerado()
    {

        if (isset($_POST["editarCantidades"])) {
            $dividir = substr($_POST["editarBarra"], -1);
            $cantidad = strlen($_POST["editarBarra"]);
            if ($cantidad == 11) {
                if ($dividir == 'A') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "2";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == 'B') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "3";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == 'C') {
                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "4";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == 'D') {

                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "5";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == 'E') {

                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "6";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == 'F') {

                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "7";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == '1') {

                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "2";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == '2') {

                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "3";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == '3') {

                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "4";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == '4') {

                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "5";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == '5') {

                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "6";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == '6') {

                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "7";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == '7') {

                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "8";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos2);
                } elseif ($dividir == '8') {

                    $datos = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $_POST["editarCantidades"],
                        "editarBarra" => $_POST["editarBarra"]
                    );

                    $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                    $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "9";
                    $datos2 = array(
                        "codigo" => $_POST["editarCodigo"],
                        "usuario" => $_POST["usuario"],
                        "articulo" => $_POST["editarArticulo"],
                        "operacion" => $_POST["editarCodOperaciones"],
                        "cantidad" => $cantidad2,
                        "editarBarra" => $codigoBarraNuevo
                    );
                    $tabla = "entallerjf";
                    $id = $_POST["editarTaller"];
                    $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                    $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos);
                    $respuesta = ModeloTalleres::mdlIngresarTaller($datos2);
                }
            } else {
                $datos = array(
                    "codigo" => $_POST["editarCodigo"],
                    "usuario" => $_POST["usuario"],
                    "articulo" => $_POST["editarArticulo"],
                    "operacion" => $_POST["editarCodOperaciones"],
                    "cantidad" => $_POST["editarCantidades"],
                    "editarBarra" => $_POST["editarBarra"]
                );

                $cantidad2 = $_POST["cantidades"] - $_POST["editarCantidades"];
                $codigoBarraNuevo = $_POST["editarCodigo"] . $_POST["editarCodOperaciones"] . "1";
                $datos2 = array(
                    "codigo" => $_POST["editarCodigo"],
                    "usuario" => $_POST["usuario"],
                    "articulo" => $_POST["editarArticulo"],
                    "operacion" => $_POST["editarCodOperaciones"],
                    "cantidad" => $cantidad2,
                    "editarBarra" => $codigoBarraNuevo
                );
                $tabla = "entallerjf";
                $id = $_POST["editarTaller"];
                $eliminar = ModeloTalleres::mdlEliminarTaller($tabla, $id);
                $respuesta = ModeloTalleres::mdlIngresarTaller($datos);
                $respuesta = ModeloTalleres::mdlIngresarTaller($datos2);
            }


            if ($respuesta == "ok") {
                $ultimo = $_POST["editarBarra"];
                $valor = $_POST["editarArticulo"];
                $rpt_articulo = ModeloArticulos::mdlMostrarArticulos($valor);
                $modelo = $rpt_articulo["modelo"];
                $nombre = $rpt_articulo["nombre"];
                $color = $rpt_articulo["color"];
                $talla = $rpt_articulo["talla"];
                $cantidad = $_POST["editarCantidades"];
                $cod_ope = $_POST["editarCodOperaciones"];
                $tablaop = "operacionesjf";
                $itemop = "codigo";
                $rpt_operacion = ModeloOperaciones::mdlMostrarOperaciones($tablaop, $itemop, $cod_ope);
                $nom_ope = $rpt_operacion["nombre"];

                // $ultimo2 = $codigoBarraNuevo;

                echo '<script>
                    
                    window.open("vistas/reportes_ticket/produccion_ticket_dividir2.php?ultimo=' . $ultimo . '&modelo=' . $modelo . '&nombre=' . $nombre . '&color=' . $color . '&talla=' . $talla . '&cant_taller=' . $cantidad . '&cod_operacion=' . $cod_ope . '&nom_operacion=' . $nom_ope . '","_blank");
                    </script>';
                echo '<script>

					swal({
						  type: "success",
						  title: "La cantidad ha sido cambiada correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "en-tallerp";

									}
								})

					</script>';
            }
        }
    }

    /*=============================================
	Dividir Cantidad Taller Generado
	=============================================*/

    static public function ctrRegresarCantidadGenerado()
    {

        if (isset($_POST["regresarCantidades"])) {
            $barraAntiguo = ModeloTalleres::mdlMostrarTalleresGenerados2($_POST["regresarBarra"]);
            // var_dump($barraAntiguo);
            $cantidad2 = $barraAntiguo["cantidad"] + $_POST["regresarCantidades"];
            $datos2 = array(
                "codigo" => $_POST["regresarCodigo"],
                "usuario" => $_POST["usuario"],
                "articulo" => $_POST["regresarArticulo"],
                "operacion" => $_POST["regresarCodOperaciones"],
                "cantidad" => $cantidad2,
                "editarBarra" => $_POST["regresarBarra"]
            );
            $tabla = "entallerjf";
            $id = $_POST["regresarTaller"];
            $estadoAntiguo = $barraAntiguo["estado"];


            if ($estadoAntiguo == "1") {
                $eliminar = ModeloTalleres::mdlEliminarTallerGenerado($tabla, $id);
                $eliminarAntiguo = ModeloTalleres::mdlEliminarTallerGenerado($tabla, $barraAntiguo["id"]);
                $respuesta2 = ModeloTalleres::mdlIngresarTaller($datos2);

                echo '<script>
    
                        swal({
                              type: "success",
                              title: "La cantidad ha sido regresada correctamente",
                              showConfirmButton: true,
                              confirmButtonText: "Cerrar"
                              }).then(function(result){
                                        if (result.value) {
    
                                        window.location = "en-tallerp";
    
                                        }
                                    })
    
                        </script>';
            } else {
                echo '<script>
    
                    swal({
                          type: "error",
                          title: "El codigo de barra anterior ya fue terminado",
                          showConfirmButton: true,
                          confirmButtonText: "Cerrar"
                          }).then(function(result){
                                    if (result.value) {

                                    window.location = "en-tallerp";

                                    }
                                })

                    </script>';
            }
        }
    }

    static public function ctrCrearTicket()
    {

        if (isset($_POST["verCantidad"])) {
            $datosCab = array(
                "articulo" => $_POST["verArti"],
                "usuario" => $_POST["verUser"],
                "cantidad" => $_POST["verCantidad"],
                "estado" => "1"
            );
            $respuestaCab = ModeloCortes::mdlMandarTallerCab($datosCab);



            $ultId = ModeloCortes::mdlUltCodigo();

            $datos = array(
                "codigo" => $ultId["ult_codigo"],
                "usuario" => $_POST["verUser"],
                "articulo" => $_POST["verArti"],
                "operacion" => $_POST["verCodOP"],
                "cantidad" => $_POST["verCantidad"],
                "editarBarra" => $ultId["ult_codigo"] . $_POST["verCodOP"]
            );

            $respuesta = ModeloTalleres::mdlIngresarTaller($datos);

            if ($respuesta == "ok") {
                $ultimo = $ultId["ult_codigo"] . $_POST["verCodOP"];
                $valor = $_POST["verArti"];
                $rpt_articulo = ModeloArticulos::mdlMostrarArticulos($valor);
                $modelo = $rpt_articulo["modelo"];
                $nombre = $rpt_articulo["nombre"];
                $color = $rpt_articulo["color"];
                $talla = $rpt_articulo["talla"];
                $cantidad = $_POST["verCantidad"];
                $cod_ope = $_POST["verCodOP"];
                $tablaop = "operacionesjf";
                $itemop = "codigo";
                $rpt_operacion = ModeloOperaciones::mdlMostrarOperaciones($tablaop, $itemop, $cod_ope);
                $nom_ope = $rpt_operacion["nombre"];

                echo '<script>

                window.open("vistas/reportes_ticket/produccion_ticket_detalle.php?ultimo=' . $ultimo . '&modelo=' . $modelo . '&nombre=' . $nombre . '&color=' . $color . '&talla=' . $talla . '&cant_taller=' . $cantidad . '&cod_operacion=' . $cod_ope . '&nom_operacion=' . $nom_ope . '","_blank");
                </script>';

                echo '<script>
                    swal({
						  type: "success",
						  title: "El surtido ha sido creado correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "en-taller";

									}
								})

					</script>';
            }
        }
    }

    /*=============================================
    REIMPRIMIR TICKET SIN REGISTRAR EN BD
    Solo genera el ticket para tickets perdidos/reimpresión
    =============================================*/
    static public function ctrReimprimirTicket()
    {
        if (isset($_POST["reimprimirCantidad"])) {
            $valor = $_POST["reimprimirArti"];
            $rpt_articulo = ModeloArticulos::mdlMostrarArticulos($valor);
            
            if ($rpt_articulo) {
                $modelo = $rpt_articulo["modelo"];
                $nombre = $rpt_articulo["nombre"];
                $color = $rpt_articulo["color"];
                $talla = $rpt_articulo["talla"];
                $cantidad = $_POST["reimprimirCantidad"];
                $cod_ope = $_POST["reimprimirCodOP"];
                
                // Usar el código de barra existente
                $ultimo = $_POST["reimprimirBar"];
                
                $tablaop = "operacionesjf";
                $itemop = "codigo";
                $rpt_operacion = ModeloOperaciones::mdlMostrarOperaciones($tablaop, $itemop, $cod_ope);
                $nom_ope = $rpt_operacion["nombre"];

                echo '<script>
                window.open("vistas/reportes_ticket/produccion_ticket_detalle.php?ultimo=' . $ultimo . '&modelo=' . $modelo . '&nombre=' . $nombre . '&color=' . $color . '&talla=' . $talla . '&cant_taller=' . $cantidad . '&cod_operacion=' . $cod_ope . '&nom_operacion=' . $nom_ope . '","_blank");
                </script>';

                echo '<script>
                    swal({
                          type: "success",
                          title: "Ticket reimpreso correctamente",
                          showConfirmButton: true,
                          confirmButtonText: "Cerrar"
                          }).then(function(result){
                                    if (result.value) {
                                    window.location = "en-taller";
                                    }
                                })
                </script>';
            } else {
                echo '<script>
                    swal({
                          type: "error",
                          title: "Error al obtener datos del artículo",
                          showConfirmButton: true,
                          confirmButtonText: "Cerrar"
                          }).then(function(result){
                                    if (result.value) {
                                    window.location = "en-taller";
                                    }
                                })
                </script>';
            }
        }
    }

    static public function ctrCrearCompensacion()
    {

        if (isset($_POST["verCantidad2"])) {
            $datosCab = array(
                "articulo" => $_POST["verArti"],
                "usuario" => $_POST["verUser"],
                "cantidad" => $_POST["verCantidad2"],
                "estado" => "1"
            );
            $respuestaCab = ModeloCortes::mdlMandarTallerCab($datosCab);



            $ultId = ModeloCortes::mdlUltCodigo();

            $datos = array(
                "codigo" => $ultId["ult_codigo"],
                "usuario" => $_POST["verUser"],
                "articulo" => $_POST["verArti"],
                "operacion" => $_POST["verCodOP"],
                "cantidad" => $_POST["verCantidad2"],
                "editarBarra" => $ultId["ult_codigo"] . $_POST["verCodOP"]
            );

            $respuesta = ModeloTalleres::mdlIngresarTaller($datos);

            if ($respuesta == "ok") {
                $ultimo = $ultId["ult_codigo"] . $_POST["verCodOP"];
                $valor = $_POST["verArti"];
                $rpt_articulo = ModeloArticulos::mdlMostrarArticulos($valor);
                $modelo = $rpt_articulo["modelo"];
                $nombre = $rpt_articulo["nombre"];
                $color = $rpt_articulo["color"];
                $talla = $rpt_articulo["talla"];
                $cantidad = $_POST["verCantidad2"];
                $cod_ope = $_POST["verCodOP"];
                $tablaop = "operacionesjf";
                $itemop = "codigo";
                $rpt_operacion = ModeloOperaciones::mdlMostrarOperaciones($tablaop, $itemop, $cod_ope);
                $nom_ope = $rpt_operacion["nombre"];

                echo '<script>

                window.open("vistas/reportes_ticket/produccion_ticket_detalle.php?ultimo=' . $ultimo . '&modelo=' . $modelo . '&nombre=' . $nombre . '&color=' . $color . '&talla=' . $talla . '&cant_taller=' . $cantidad . '&cod_operacion=' . $cod_ope . '&nom_operacion=' . $nom_ope . '","_blank");
                </script>';

                echo '<script>
                    swal({
						  type: "success",
						  title: "La compensacion ha sido creada correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "en-taller";

									}
								})

					</script>';
            }
        }
    }

    /*
     * Crear ticket original: registra solo en entaller (detalle) para control, NO en cabecera (enviados a taller).
     */
    static public function ctrCrearTicketOriginal()
    {

        if (isset($_POST["ticketArticulo"])) {

            /* Igual que creación global: insertar cabecera primero para obtener id_cabecera.
               El código del ticket = id_cabecera + codigo_operacion (concatenados).
               taller='TX' marca ticket original para excluirlo de enviados-taller. */
            $datosCab = array(
                "articulo" => $_POST["ticketArticulo"],
                "usuario" => $_POST["ticketUser"],
                "cantidad" => $_POST["ticketCantidad"],
                "estado" => "1",
                "taller" => "TX"
            );
            $respuestaCab = ModeloCortes::mdlMandarTallerCab($datosCab);

            $ultId = ModeloCortes::mdlUltCodigo();
            $ultimo = $ultId["ult_codigo"] . $_POST["ticketOperacion"];

            $datos = array(
                "codigo" => $ultId["ult_codigo"],
                "usuario" => $_POST["ticketUser"],
                "articulo" => $_POST["ticketArticulo"],
                "operacion" => $_POST["ticketOperacion"],
                "cantidad" => $_POST["ticketCantidad"],
                "editarBarra" => $ultimo
            );

            $respuesta = ModeloTalleres::mdlIngresarTaller($datos);

            if ($respuesta == "ok") {

                $valor = $_POST["ticketArticulo"];
                $rpt_articulo = ModeloArticulos::mdlMostrarArticulos($valor);
                $modelo = $rpt_articulo["modelo"];
                $nombre = $rpt_articulo["nombre"];
                $color = $rpt_articulo["color"];
                $talla = $rpt_articulo["talla"];
                $cantidad = $_POST["ticketCantidad"];
                $cod_ope = $_POST["ticketOperacion"];
                $tablaop = "operacionesjf";
                $itemop = "codigo";
                $rpt_operacion = ModeloOperaciones::mdlMostrarOperaciones($tablaop, $itemop, $cod_ope);
                $nom_ope = $rpt_operacion["nombre"];

                echo '<script>
    
                    window.open("vistas/reportes_ticket/produccion_ticket_detalle.php?ultimo=' . $ultimo . '&modelo=' . $modelo . '&nombre=' . $nombre . '&color=' . $color . '&talla=' . $talla . '&cant_taller=' . $cantidad . '&cod_operacion=' . $cod_ope . '&nom_operacion=' . $nom_ope . '","_blank");
                    </script>';
                echo '<script>

                    swal({
                          type: "success",
                          title: "Se mando a taller correctamente",
                          showConfirmButton: true,
                          confirmButtonText: "Cerrar"
                          }).then(function(result){
                            if (result.value) {

                            window.location = "en-taller";

                            }
                        })

                    </script>';
            }
        }
    }


    /* 
	* Asignar codigo de barra a trabajador
	*/

    static public function ctrActualizarGenerado()
    {

        if (isset($_POST["salidaArticulo"])) {

            $valor = $_POST["salidaArticulo"];
            $respuesta = ModeloTalleres::mdlActualizarArticuloGenerado($valor);
            // var_dump($respuesta);

            if ($respuesta == "ok") {

                echo '<script>
                    swal({
						  type: "success",
						  title: "Los talleres cerrados fueron generados correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "en-tallerp";

									}
								})

					</script>';
            }
        }
    }

    /*
     * Compensación por día (op. 832 COMPENSACION).
     * Tipos: dia_sueldo | medio_sueldo | dia_promedio | medio_promedio
     * concepto: texto libre que se muestra en lugar del nombre de operación.
     * cantidad = ROUND(monto * 12, 2) con precio_doc = 1.
     * Ticket queda terminado (estado 3) y asignado al trabajador; total_precio = monto.
     */
    static public function ctrCrearCompensacionFeriado($codTrabajadores, $fechaFeriado, $usuario, $tipo = "dia_sueldo", $concepto = "", $diasTrabajados = 15)
    {
        $articulo = "C001251";
        $operacion = "832";
        $creados = array();
        $errores = array();

        $tiposValidos = array("dia_sueldo", "medio_sueldo", "dia_promedio", "medio_promedio");
        if (!in_array($tipo, $tiposValidos, true)) {
            $tipo = "dia_sueldo";
        }

        $diasTrabajados = (float) $diasTrabajados;
        if ($diasTrabajados <= 0) {
            $diasTrabajados = 15;
        }

        $concepto = trim(preg_replace('/\s+/', ' ', (string) $concepto));
        if ($concepto === "") {
            return array("ok" => false, "mensaje" => "Indique el concepto de la compensación", "creados" => $creados, "errores" => $errores);
        }
        if (function_exists("mb_substr")) {
            $concepto = mb_substr($concepto, 0, 120, "UTF-8");
        } else {
            $concepto = substr($concepto, 0, 120);
        }

        if (!is_array($codTrabajadores) || count($codTrabajadores) === 0) {
            return array("ok" => false, "mensaje" => "Seleccione al menos un trabajador", "creados" => $creados, "errores" => $errores);
        }

        $fechaFeriado = trim((string) $fechaFeriado);
        if ($fechaFeriado === "" || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFeriado)) {
            return array("ok" => false, "mensaje" => "Fecha inválida", "creados" => $creados, "errores" => $errores);
        }

        date_default_timezone_set("America/Lima");
        $hora = date("G:i:s");
        $fechaTicket = $fechaFeriado . " " . $hora;

        $usaPromedio = ($tipo === "dia_promedio" || $tipo === "medio_promedio");
        $quincena = null;
        $promedios = array();

        if ($usaPromedio) {
            $quincena = ModeloTrabajador::mdlQuincenaAnterior($fechaFeriado);
            if (!$quincena) {
                return array(
                    "ok" => false,
                    "mensaje" => "No hay quincena registrada anterior a la fecha del feriado",
                    "creados" => $creados,
                    "errores" => $errores
                );
            }
            $promedios = ModeloTrabajador::mdlPromedioDiarioProduccion(
                $quincena["inicio"],
                $quincena["fin"]
            );
        }

        $pdoCheck = Conexion::conectar()->prepare(
            "SELECT od.precio_doc
             FROM operaciones_detallejf od
             INNER JOIN operacionesjf o ON o.codigo = od.cod_operacion
             WHERE od.modelo = 'C001' AND od.cod_operacion = :operacion
             LIMIT 1"
        );
        $pdoCheck->bindParam(":operacion", $operacion, PDO::PARAM_STR);
        $pdoCheck->execute();
        $opDetalle = $pdoCheck->fetch(PDO::FETCH_ASSOC);
        if (!$opDetalle) {
            return array(
                "ok" => false,
                "mensaje" => "Falta configurar operación 832 COMPENSACION en modelo C001. Ejecute docs/sql/compensacion-dia-concepto.sql",
                "creados" => $creados,
                "errores" => $errores
            );
        }

        foreach ($codTrabajadores as $codTra) {
            $codTra = (int) $codTra;
            if ($codTra <= 0) {
                continue;
            }

            $trab = ModeloTrabajador::mdlMostrarTrabajador("trabajadorjf", "cod_tra", $codTra);
            if (!$trab || !is_array($trab)) {
                $errores[] = array("cod_tra" => $codTra, "motivo" => "Trabajador no encontrado");
                continue;
            }

            $nombre = isset($trab["nombre"]) ? $trab["nombre"] : ("#" . $codTra);
            if (!isset($trab["estado"]) || $trab["estado"] !== "Activo") {
                $errores[] = array("cod_tra" => $codTra, "nombre" => $nombre, "motivo" => "No está activo");
                continue;
            }

            $sueldo = isset($trab["sueldo_total"]) ? (float) $trab["sueldo_total"] : 0;
            $diaSueldo = $sueldo > 0 ? round($sueldo / 30, 2) : 0;
            $total = isset($promedios[$codTra]) ? round((float) $promedios[$codTra], 2) : 0;
            $diaPromedio = ($total > 0 && $diasTrabajados > 0)
                ? round($total / $diasTrabajados, 2)
                : 0;

            if ($tipo === "dia_sueldo") {
                if ($sueldo <= 0) {
                    $errores[] = array("cod_tra" => $codTra, "nombre" => $nombre, "motivo" => "Sin sueldo total");
                    continue;
                }
                $monto = $diaSueldo;
                $base = $sueldo;
            } elseif ($tipo === "medio_sueldo") {
                if ($sueldo <= 0) {
                    $errores[] = array("cod_tra" => $codTra, "nombre" => $nombre, "motivo" => "Sin sueldo total");
                    continue;
                }
                $monto = round($diaSueldo / 2, 2);
                $base = $sueldo;
            } elseif ($tipo === "dia_promedio") {
                if ($total <= 0) {
                    $errores[] = array("cod_tra" => $codTra, "nombre" => $nombre, "motivo" => "Sin total en quincena pasada");
                    continue;
                }
                $monto = $diaPromedio;
                $base = $total;
            } else {
                if ($total <= 0) {
                    $errores[] = array("cod_tra" => $codTra, "nombre" => $nombre, "motivo" => "Sin total en quincena pasada");
                    continue;
                }
                $monto = round($diaPromedio / 2, 2);
                $base = $total;
            }

            $cantidad = round($monto * 12, 2);
            if ($cantidad <= 0) {
                $errores[] = array("cod_tra" => $codTra, "nombre" => $nombre, "motivo" => "Cantidad calculada inválida");
                continue;
            }

            $montoStr = number_format($monto, 2, ".", "");
            $cantidadStr = number_format($cantidad, 2, ".", "");

            $datosCab = array(
                "articulo" => $articulo,
                "usuario" => $usuario,
                "cantidad" => $cantidadStr,
                "estado" => "1",
                "taller" => "TX"
            );
            $respuestaCab = ModeloCortes::mdlMandarTallerCab($datosCab);
            if ($respuestaCab !== "ok") {
                $errores[] = array("cod_tra" => $codTra, "nombre" => $nombre, "motivo" => "Error al crear cabecera");
                continue;
            }

            $ultId = ModeloCortes::mdlUltCodigo();
            $idCab = $ultId["ult_codigo"];
            $codigoBarra = $idCab . $operacion;

            $datos = array(
                "codigo" => $idCab,
                "usuario" => $usuario,
                "articulo" => $articulo,
                "operacion" => $operacion,
                "concepto" => $concepto,
                "cantidad" => $cantidadStr,
                "monto" => $montoStr,
                "editarBarra" => $codigoBarra,
                "trabajador" => $codTra,
                "fecha_proceso" => $fechaTicket,
                "fecha_terminado" => $fechaTicket
            );

            $respuesta = ModeloTalleres::mdlIngresarTallerFeriado($datos);
            if ($respuesta !== "ok") {
                $errores[] = array("cod_tra" => $codTra, "nombre" => $nombre, "motivo" => "Error al crear ticket terminado");
                continue;
            }

            $creados[] = array(
                "cod_tra" => $codTra,
                "nombre" => $nombre,
                "sueldo_total" => round($sueldo, 2),
                "base_calculo" => $base,
                "tipo" => $tipo,
                "concepto" => $concepto,
                "monto" => $monto,
                "cantidad" => $cantidad,
                "codigo" => $codigoBarra
            );
        }

        $ok = count($creados) > 0;
        $mensaje = $ok
            ? ("Se crearon " . count($creados) . " ticket(s) de compensación")
            : "No se creó ningún ticket de compensación";

        if (count($errores) > 0) {
            $mensaje .= ". Errores: " . count($errores);
        }

        return array(
            "ok" => $ok,
            "mensaje" => $mensaje,
            "creados" => $creados,
            "errores" => $errores,
            "tipo" => $tipo,
            "concepto" => $concepto,
            "quincena" => $quincena
        );
    }

    /*=============================================
    CAMBIAR TALLER DE UN BLOQUE ENVIADO POR ERROR
    Ver reglas en ModeloTalleres::mdlCambiarTallerBloque
    =============================================*/
    static public function ctrCambiarTallerBloque()
    {
        if (!isset($_POST["bloqueCambiarTaller"])) {
            return;
        }

        $idCab = (int) $_POST["bloqueCambiarTaller"];
        $nuevoTaller = isset($_POST["tallerNuevoCambiar"]) ? trim((string) $_POST["tallerNuevoCambiar"]) : "";

        $respuesta = ModeloTalleres::mdlCambiarTallerBloque($idCab, $nuevoTaller);

        if (is_array($respuesta) && isset($respuesta["status"]) && $respuesta["status"] === "ok") {
            $texto = "Bloque " . $idCab . " (" . $respuesta["articulo"] . " x " . $respuesta["cantidad"] . "): "
                . $respuesta["taller_anterior"] . " → " . $respuesta["taller_nuevo"];
            echo '<script>
            swal({
                type: "success",
                title: "Taller cambiado correctamente",
                text: ' . json_encode($texto) . ',
                showConfirmButton: true,
                confirmButtonText: "Cerrar"
            }).then(function(result){
                if (result.value) {
                    window.location = "en-taller";
                }
            })
            </script>';
            return;
        }

        $mensajes = array(
            "error_datos_incompletos" => "Seleccione el bloque y el taller nuevo.",
            "error_movimiento_no_encontrado" => "No se encontró el bloque de taller.",
            "error_mismo_taller" => "El taller nuevo es el mismo que el actual.",
            "error_taller_no_existe" => "El taller seleccionado no existe.",
            "error_tipo_distinto" => "No se puede pasar de taller interno a servicio externo (o al revés). Use «Eliminar bloque» y vuelva a enviar desde En Cortes.",
            "error_no_se_pudo_crear_servicio" => "No se pudo obtener/crear el servicio del día del taller nuevo.",
            "error_bloque_modificado" => "El bloque cambió mientras lo editaba. Vuelva a intentarlo.",
            "error_bloque_ya_procesado" => "El bloque ya fue procesado, no se puede cambiar de taller.",
            "error_bloque_con_ingresos" => "El bloque ya tiene ingresos/terminados (saldo menor a la cantidad). No se puede cambiar de taller.",
            "error_bloque_con_tickets_en_proceso" => "El bloque tiene tickets asignados, en proceso o terminados. No se puede cambiar de taller.",
            "error_detalle_servicio_no_encontrado" => "No se encontró un único detalle de servicio ligado al bloque.",
            "error_servicio_con_ingresos" => "El servicio del bloque ya tiene ingresos. No se puede cambiar de taller.",
            "error_servicio_no_activo" => "El servicio original ya no está ACTIVO. No se puede cambiar de taller."
        );
        $msg = is_string($respuesta) && isset($mensajes[$respuesta])
            ? $mensajes[$respuesta]
            : "No se pudo cambiar el taller: " . (is_string($respuesta) ? $respuesta : "error desconocido");

        echo '<script>
        swal({
            type: "error",
            title: "No se cambió el taller",
            text: ' . json_encode($msg) . ',
            showConfirmButton: true,
            confirmButtonText: "Cerrar"
        })
        </script>';
    }
}
