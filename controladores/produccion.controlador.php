<?php

use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

class ControladorProduccion
{

    /* 
    *MOSTRAR QUINCENAS
    */
    static public function ctrMostrarQuincenas($valor)
    {

        $respuesta = ModeloProduccion::mdlMostrarQuincenas($valor);

        return $respuesta;
    }

    /* 
    *MOSTRAR AVANCES
    */
    static public function ctrMostrarAvances($inicio, $fin)
    {

        $respuesta = ModeloProduccion::mdlMostrarAvances($inicio, $fin);

        return $respuesta;
    }

    /* 
	* CREAR QUINCENA
	*/
    static public function ctrCrearQuincenas()
    {

        if (isset($_POST["mes"])) {

            $datos = array(
                "ano" => $_POST["año"],
                "mes" => $_POST["mes"],
                "quincena" => $_POST["quincena"],
                "inicio" => $_POST["inicio"],
                "fin" => $_POST["fin"],
                "usuario" => $_POST["usuario"]
            );
            //var_dump($datos);

            $respuesta = ModeloProduccion::mdlCrearQuincenas($datos);

            if ($respuesta == "ok") {

                echo '<script>

                    swal({
                          type: "success",
                          title: "La quincena ha sido guardada correctamente",
                          showConfirmButton: true,
                          confirmButtonText: "Cerrar"
                          }).then(function(result){
                                    if (result.value) {

                                    window.location = "quincena";

                                    }
                                })

                    </script>';
            }
        }
    }

    /* 
    *EDITAR QUINCENA
    */

    static public function ctrEditarQuincenas()
    {

        if (isset($_POST["editarMes"])) {

            $datos = array(
                "id" => $_POST["id"],
                "ano" => $_POST["editarAño"],
                "mes" => $_POST["editarMes"],
                "quincena" => $_POST["editarQuincena"],
                "inicio" => $_POST["editarInicio"],
                "fin" => $_POST["editarFin"],
                "usuario" => $_POST["editarUsuario"]
            );


            $respuesta = ModeloProduccion::mdlEditarQuincenas($datos);

            if ($respuesta == "ok") {

                echo '<script>

                swal({
                      type: "success",
                      title: "La quincena ha sido cambiada correctamente",
                      showConfirmButton: true,
                      confirmButtonText: "Cerrar"
                      }).then(function(result){
                                if (result.value) {

                                window.location = "quincena";

                                }
                            })

                </script>';
            }
        }
    }

    /* 
    *MOSTRAR EFICIENCIA QUINCENAL
    */
    static public function ctrMostrarEficiencia($inicio, $fin, $nquincena, $id, $sector)
    {

        $respuesta = ModeloProduccion::mdlMostrarEficiencia($inicio, $fin, $nquincena, $id, $sector);

        return $respuesta;
    }

    /* 
    *MOSTRAR EFICIENCIA QUINCENAL
    */
    static public function ctrTablaEficienciaGlobal($taller)
    {

        $respuesta = ModeloProduccion::mdlTablaEficienciaGlobal($taller);

        return $respuesta;
    }

    /* 
    *MOSTRAR PAGOS QUINCENAL
    */
    static public function ctrMostrarPagos($inicio, $fin, $nquincena, $id, $sector)
    {

        $respuesta = ModeloProduccion::mdlMostrarPagos($inicio, $fin, $nquincena, $id, $sector);

        return $respuesta;
    }

    /* 
	* BORRAR ARTICULO
	*/
    static public function ctrEliminarQuincena()
    {

        if (isset($_GET["idQuincena"])) {

            //var_dump($_GET["idQuincena"]);

            $id = $_GET["idQuincena"];

            $respuesta = ModeloProduccion::mdlEliminarQuincena($id);

            if ($respuesta == "ok") {

                //var_dump($respuesta);

                echo '<script>

				swal({
					  type: "success",
					  title: "La quincena ha sido borrada correctamente",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar"
					  }).then(function(result){
								if (result.value) {

								window.location = "quincena";

								}
							})

				</script>';
            }
        }
    }

    static public function ctrImprimirAvance()
    {

        if (isset($_GET["inicioQuincena"]) && isset($_GET["finQuincena"])) {

            $inicio = $_GET["inicioQuincena"];

            $fin = $_GET["finQuincena"];


            $nombre_impresora = "Star BSC10";

            $connector = new WindowsPrintConnector($nombre_impresora);
            $printer = new Printer($connector);

            $fecha = date("d-m-Y");

            $respuesta = ControladorProduccion::ctrMostrarAvances($inicio, $fin);
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
                $printer->text("AVANCE PAGO DE " . $inicio . " AL " . $fin . "\n"); //Nombre de la empresa

                $printer->text("=======================================" . "\n"); //Dirección de la empresa
                //Quitamos negrita


                $printer->setJustification(Printer::JUSTIFY_LEFT);

                $printer->text("ID:" . $value["id_trabajador"] . "\n");

                $printer->setEmphasis(false);

                $printer->text("Nombre:     " . $value["nombre"] . "\n");

                $printer->text("Produccion:                 " . $value["produccion"] . "\n");

                $printer->text("Sueldo:                     " . $value["sueldo"] . "\n");

                $diferencia = substr($value["diferencia"], 0, 1);
                $tamano = strlen($value["diferencia"]);

                if ($diferencia == "-") {
                    if ($tamano == 7) {
                        $printer->text("Diferencia:                " . $value["diferencia"] . "\n");
                    } else {
                        $printer->text("Diferencia:                 " . $value["diferencia"] . "\n");
                    }
                } else {

                    $printer->text("Incentivo:                  " . $value["incentivo"] . "\n");
                }





                //Activamos negrita
                $printer->setEmphasis(true);

                $printer->text("---------------------------------------" . "\n"); //Divisor Total

                $printer->text("Total:                      " . $value["total"] . "\n");


                $printer->text("---------------------------------------" . "\n"); //Divisor Total

                $printer->feed(1);

                $printer->cut();
            }

            $printer->close();

            echo '<script>

            swal({
                  type: "success",
                  title: "Se imprimio el avance correctamente",
                  showConfirmButton: true,
                  confirmButtonText: "Cerrar"
                  }).then(function(result){
                            if (result.value) {

                            window.location = "quincena";

                            }
                        })

            </script>';
        }
    }

    /* 
    *MOSTRAR TRABAJADORES POR TALLER
    */
    static public function ctrMostrarTrabTaller($taller)
    {

        $respuesta = ModeloProduccion::mdlMostrarTrabTaller($taller);

        return $respuesta;
    }

    // // prehormado
    // static public function ctrCrearPrehormado()
    // {
    //     if (isset($_POST["tipoPrehormado"])) {

    //         // declaramos las variables
    //         $tipo = $_POST["tipoPrehormado"];
    //         $fecha_registro = $_POST["fechaPrehormado"];
    //         $articulo = $_POST["articulosPrehormado"];
    //         $cantidad = $_POST["cantidadPrehormado"];
    //         $usureg = $_SESSION["usuario"];
    //         $fecreg = date("Y-m-d");
    //         $pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);

    //         // creamos el array
    //         $datos = array(
    //             "tipo" => $tipo,
    //             "fecha_registro" => $fecha_registro,
    //             "articulo" => $articulo,
    //             "cantidad" => $cantidad,
    //             "usureg" => $usureg,
    //             "fecreg" => $fecreg,
    //             "pcreg" => $pcreg
    //         );

    //         $respuesta = ModeloProduccion::mdlCrearPrehormado($datos);

    //         if ($respuesta == "ok") {

    //             echo '<script>

    //             // toast de exito
    //             Command: toastr["success"](
    //                 "Editado de materia prima exitosamente!"
    //             );
    //             window.location = "prehormado";

    //             </script>';
    //         }
    //     }
    // }

    public static function ctrCrearPrehormado()
    {
        // Solo continuar con POST y si viene el campo clave
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        if (!isset($_POST['tipoPrehormadoPS'])) return;

        // Datos del formulario (sin ??)
        $tipoPS   = isset($_POST['tipoPrehormadoPS']) ? $_POST['tipoPrehormadoPS'] : null;     // 'producto' | 'servicio'
        $fecha    = isset($_POST['nuevaFecha']) ? $_POST['nuevaFecha'] : null;                 // YYYY-MM-DD
        $jsonList = isset($_POST['listaArticulosPrehormado']) ? $_POST['listaArticulosPrehormado'] : '[]';
        $totalUI  = isset($_POST['totalTaller']) ? (int)$_POST['totalTaller'] : 0;

        // Metadatos
        $usureg = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'sistema';
        $fecreg = date('Y-m-d');
        $pcreg  = (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] !== '')
            ? gethostbyaddr($_SERVER['REMOTE_ADDR'])
            : '127.0.0.1';

        // Validaciones básicas
        $items = json_decode($jsonList, true);
        if (!is_array($items)) {
            $items = array();
        }

        if (!$tipoPS || !$fecha || empty($items)) {
            echo '<script>Command: toastr["error"]("Faltan datos: tipo, fecha o lista vacía.");</script>';
            return;
        }

        // Normalizar lista
        $rows = array();
        $totalCalc = 0;
        foreach ($items as $it) {
            $id       = isset($it['id']) ? trim((string)$it['id']) : '';
            $cantidad = isset($it['cantidad']) ? (int)$it['cantidad'] : 0;

            if ($id === '' || $cantidad < 1) {
                continue;
            }

            $rows[] = array(
                'tipo'           => $tipoPS,
                'fecha_registro' => $fecha,
                'articulo'       => $id,      // tu código
                'cantidad'       => $cantidad,
                'usureg'         => $usureg,
                'fecreg'         => $fecreg,
                'pcreg'          => $pcreg,
            );
            $totalCalc += $cantidad;
        }

        if (empty($rows)) {
            echo '<script>Command: toastr["error"]("La lista no contiene artículos válidos.");</script>';
            return;
        }

        if ($totalUI > 0 && $totalUI !== $totalCalc) {
            echo '<script>Command: toastr["warning"]("El total calculado (' . $totalCalc . ') difiere del mostrado (' . $totalUI . '). Se usará el calculado.");</script>';
        }

        // Inserción masiva (asegúrate de tener este método en tu modelo)
        $resp = ModeloProduccion::mdlCrearPrehormadoMasivo($rows);
        echo '<pre>';
        print_r($resp);
        echo '</pre>';

        if ($resp === 'ok') {
            echo '<script>
            Command: toastr["success"]("Prehormado creado con ' . count($rows) . ' ítems (total: ' . $totalCalc . ').");
            window.location = "prehormado";
        </script>';
        } else {
            echo '<script>Command: toastr["error"]("No se pudo registrar el prehormado. Intenta nuevamente.");</script>';
        }
    }


    static public function ctrEditarPrehormado($id)
    {
        if (isset($_POST["idPrehormado"])) {

            // declaramos las variables
            $id = $_POST["idPrehormado"];
            $tipo = $_POST["tipoPrehormado"];
            $fecha_registro = $_POST["fechaPrehormado"];
            $articulo = $_POST["articulosPrehormado"];
            $cantidad = $_POST["cantidadPrehormado"];

            // creamos el array
            $datos = array(
                "id" => $id,
                "tipo" => $tipo,
                "fecha_registro" => $fecha_registro,
                "articulo" => $articulo,
                "cantidad" => $cantidad
            );

            $respuesta = ModeloProduccion::mdlEditarPrehormado($datos);

            if ($respuesta == "ok") {

                echo '<script>

                // toast de exito
                Command: toastr["success"](
                    "Editado de materia prima exitosamente!"
                );
                window.location = "prehormado";

                </script>';
            }
        }
    }

    /*=============================================
    REPORTE: pagos trusas por monto producido total_precio (Excel)
    =============================================*/
    static public function ctrRptPagosTrusasProduccionQuincena($id)
    {
        return ModeloProduccion::mdlRptPagosTrusasProduccionQuincena($id);
    }

    static public function ctrRptPagosTrusasProduccionCabecera($inicio, $fin)
    {
        return ModeloProduccion::mdlRptPagosTrusasProduccionCabecera($inicio, $fin);
    }

    static public function ctrRptPagosTrusasProduccionDetalle($inicio, $fin)
    {
        return ModeloProduccion::mdlRptPagosTrusasProduccionDetalle($inicio, $fin);
    }

    static public function ctrRptDetalleProduccionTrabajadorInfo($inicio, $fin, $trabajador)
    {
        return ModeloProduccion::mdlRptDetalleProduccionTrabajadorInfo($inicio, $fin, $trabajador);
    }

    static public function ctrRptDetalleProduccionTrabajadorDetalle($inicio, $fin, $trabajador)
    {
        return ModeloProduccion::mdlRptDetalleProduccionTrabajadorDetalle($inicio, $fin, $trabajador);
    }

    static public function ctrRptDetalleProduccionTrabajadoresIds($inicio, $fin, $sector = null)
    {
        return ModeloProduccion::mdlRptDetalleProduccionTrabajadoresIds($inicio, $fin, $sector);
    }
}
