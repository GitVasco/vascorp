<?php

class ControladorArreglos
{
    static public function ctrCrearArreglos()
    {

        if (isset($_POST["idUsuario"])) {

            if ($_POST["listaArticulosIngreso"] == "") {
                echo '<script>
                swal({
                    type: "error",
                    title: "Error",
                    text: "¡No se seleccionó ningún artículo. Por favor, intenteló de nuevo!",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                }).then((result)=>{
                    if(result.value){
                        window.location="crear-arreglos";}
                });
            </script>';

                return;
            }

            $articulosLista = json_decode($_POST["listaArticulosIngreso"], true);

            if ($_POST["nuevoTipoSector"] == "0") {

                foreach ($articulosLista as $key => $value) {
                    $articulos = $value["articulo"];
                    $cantidad = $value["cantidad"];

                    $respuesta = ModeloArreglos::mdlActualizarCrearArreglos($_POST["nuevoTipoSector"], $articulos, $cantidad);
                }
            } else {
                foreach ($articulosLista as $key => $value) {
                    $articulos = $value["articulo"];
                    $cantidad = $value["cantidad"];

                    $respuesta = ModeloArreglos::mdlActualizarCrearArreglos($_POST["nuevoTipoSector"], $articulos, $cantidad);

                    $idCierre = $value["idCierre"];
                    $respuesta = ModeloArreglos::mdlActualizarCierre($idCierre, $cantidad);
                }
            }

            $fecha = $_POST["nuevaFecha"];
            $datos = [
                "codigo"    => $_POST["nuevoCodigo"],
                "guia"      => $_POST["nuevaGuiaIng"],
                "usuario"   => $_POST["idUsuario"],
                "taller"    => $_POST["nuevoTalleresCrearArreglos"],
                "total"     => $_POST["totalTaller"],
                "pendiente" => $_POST["totalTaller"],
                "tipo"      => 1, //ingreso
                "fecha"     => $fecha,
                "estado"    => "Pendiente"
            ];

            $cabecera = ModeloArreglos::mdlCrearArregloCabecera($datos);

            if ($cabecera == "ok") {

                foreach ($articulosLista as $key => $value) {
                    $datosDetalle = [
                        "codigo"    => $_POST["nuevoCodigo"],
                        "articulo"  => $value["articulo"],
                        "cantidad"  => $value["cantidad"],
                        "pendiente" => $value["cantidad"],
                        "id_cierre"  => $value["idCierre"]
                    ];

                    $detalle = ModeloArreglos::mdlCrearArreglosDetalle($datosDetalle);
                }

                if ($detalle == "ok") {
                    echo '<script>
                    swal({
                        type: "success",
                        title: "¡El arreglo ha sido creado correctamente!",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar"
                    }).then((result)=>{
                        if(result.value){
                            window.location="arreglos";}
                    });
                </script>';
                }
            } else {
                echo '<script>
                swal({
                    type: "error",
                    title: "Error",
                    text: "¡No se pudo crear el arreglo. Por favor, intenteló de nuevo!",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                }).then((result)=>{
                    if(result.value){
                        window.location="crear-arreglos";}
                });
            </script>';
            }
        }
    }

    static public function ctrRangoFechasArreglos($fechaInicial, $fechaFinal)
    {
        $respuesta = ModeloArreglos::mdlRangoFechasArreglos($fechaInicial, $fechaFinal);

        return $respuesta;
    }

    static public function ctrCrearArreglosCierre()
    {
        if (isset($_POST["idUsuario"])) {

            if ($_POST["listaArticulosArreglos"] == "") {
                echo '<script>
                swal({
                    type: "error",
                    title: "Error",
                    text: "¡No se seleccionó ningún artículo. Por favor, intenteló de nuevo!",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                }).then((result)=>{
                    if(result.value){
                        window.location="cerrar-arreglos";}
                });
            </script>';

                return;
            }

            $articulosLista = json_decode($_POST["listaArticulosArreglos"], true);

            foreach ($articulosLista as $key => $value) {
                $idArreglo = $value["id"];
                $articulos = $value["articulo"];
                $cantidad = $value["cantidad"];
                $saldo = $value["saldo"];

                $cantidadDisponible = $saldo < 0 ? $cantidad + $saldo : $cantidad;

                // 1. descargamos arreglo en articulos
                $descargarArreglos = ModeloArreglos::mdlDescargarArreglos($articulos, $cantidadDisponible);

                // 2. actualizamos pendiente en arreglo detalle
                $actualizarPendiente = ModeloArreglos::mdlActualizarPendienteArreglos($idArreglo, $cantidad);

                // 3. actualizamos el stock en inventario
                $actualizarStock = ModeloArreglos::mdlActualizarStock($articulos, $cantidadDisponible);
            }

            ModeloArreglos::mdlCerrarDetalleArreglos();
            ModeloArreglos::mdlCerrarCabeceraArreglos();
            ModeloArreglos::mdlActualizarPendienteCabecera();

            $fecha = $_POST["nuevaFecha"];
            $datos = [
                "codigo"    => $_POST["nuevoCodigoCe"],
                "guia"      => $_POST["nuevaGuiaIng"],
                "usuario"   => $_POST["idUsuario"],
                "taller"    => $_POST["nuevoTalleresA"],
                "total"     => $_POST["totalArreglosCierre"],
                "pendiente" => $_POST["totalArreglosCierre"],
                "tipo"      => 2, //cierre
                "fecha"     => $fecha,
                "estado"    => "Pendiente"
            ];

            $cabecera = ModeloArreglos::mdlCrearArregloCabecera($datos);

            if ($cabecera == "ok") {

                foreach ($articulosLista as $key => $value) {
                    $datosDetalle = [
                        "codigo"    => $_POST["nuevoCodigoCe"],
                        "articulo"  => $value["articulo"],
                        "cantidad"  => $value["cantidad"],
                        "pendiente" => $value["cantidad"],
                        "id_cierre"  => ""
                    ];

                    $detalle = ModeloArreglos::mdlCrearArreglosDetalle($datosDetalle);

                    $datosMovimientos = [
                        "tipo" => "E33",
                        "documento" => $_POST["nuevoCodigoCe"],
                        "taller" => $_POST["nuevoTalleresA"],
                        "fecha" => $fecha,
                        "articulo" => $value["articulo"],
                        "cantidad" => $value["cantidad"],
                        "almacen" => "01",
                        "idcierre" => $value["id"]
                    ];
                    ModeloIngresos::mdlGuardarDetalleIngreso("movimientosjf_2025", $datosMovimientos);
                }

                if ($detalle == "ok") {
                    echo '<script>
                    swal({
                        type: "success",
                        title: "¡El arreglo ha sido creado correctamente!",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar"
                    }).then((result)=>{
                        if(result.value){
                            window.location="arreglos";}
                    });
                </script>';
                }
            } else {
                echo '<script>
                swal({
                    type: "error",
                    title: "Error",
                    text: "¡No se pudo crear el arreglo. Por favor, intenteló de nuevo!",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                }).then((result)=>{
                    if(result.value){
                        window.location="cerrar-arreglos";}
                });
            </script>';
            }
        }
    }
}
