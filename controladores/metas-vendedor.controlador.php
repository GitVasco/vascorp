<?php

class ControladorMetasVendedor
{
    private static function periodoMinimo()
    {
        date_default_timezone_set("America/Lima");

        return array(
            "anio" => (int) date("Y"),
            "mes" => (int) date("n"),
        );
    }

    private static function periodoValido($anio, $mes)
    {
        $anio = (int) $anio;
        $mes = (int) $mes;
        $min = self::periodoMinimo();

        if ($anio < $min["anio"]) {
            return false;
        }

        if ($anio === $min["anio"] && $mes < $min["mes"]) {
            return false;
        }

        return $mes >= 1 && $mes <= 12;
    }

    private static function normalizarMonto($valor)
    {
        $valor = str_replace(",", "", trim((string) $valor));

        if ($valor === "") {
            return 0.0;
        }

        return round((float) $valor, 2);
    }

    private static function normalizarMetaCobranza($valor)
    {
        $valor = trim((string) $valor);

        if ($valor === "") {
            return null;
        }

        return self::normalizarMonto($valor);
    }

    static public function ctrPeriodoActual()
    {
        date_default_timezone_set("America/Lima");

        return array(
            "anio" => (int) date("Y"),
            "mes" => (int) date("n"),
        );
    }

    static public function ctrNormalizarPeriodo($anio, $mes)
    {
        $actual = self::ctrPeriodoActual();
        $anio = (int) $anio;
        $mes = (int) $mes;

        if ($anio < $actual["anio"]) {
            $anio = $actual["anio"];
            $mes = $actual["mes"];
        } elseif ($anio === $actual["anio"] && $mes < $actual["mes"]) {
            $mes = $actual["mes"];
        }

        if ($mes < 1 || $mes > 12) {
            $mes = $actual["mes"];
            $anio = $actual["anio"];
        }

        return array("anio" => $anio, "mes" => $mes);
    }

    static public function ctrMostrarVendedores()
    {
        return ModeloMetasVendedor::mdlVendedoresActivos();
    }

    static public function ctrMostrarMeta($item, $valor)
    {
        return ModeloMetasVendedor::mdlMostrarMeta($item, $valor);
    }

    static public function ctrListarMetasPeriodo($anio, $mes)
    {
        $periodo = self::ctrNormalizarPeriodo($anio, $mes);

        return ModeloMetasVendedor::mdlListarMetasPeriodo($periodo["anio"], $periodo["mes"]);
    }

    static public function ctrCrearMeta()
    {
        if (!isset($_POST["nuevoCodVendedor"], $_POST["nuevoAnio"], $_POST["nuevoMes"])) {
            return;
        }

        $anio = (int) $_POST["nuevoAnio"];
        $mes = (int) $_POST["nuevoMes"];
        $codVendedor = trim((string) $_POST["nuevoCodVendedor"]);

        if (!self::periodoValido($anio, $mes)) {
            echo '<script>swal({type:"error",title:"Solo se permiten metas desde el mes actual en adelante"});</script>';
            return;
        }

        if ($codVendedor === "") {
            echo '<script>swal({type:"error",title:"Seleccione un vendedor"});</script>';
            return;
        }

        if (ModeloMetasVendedor::mdlExisteMetaPeriodo($codVendedor, $anio, $mes)) {
            echo '<script>swal({type:"error",title:"Ya existe una meta para ese vendedor en el período"});</script>';
            return;
        }

        $datos = array(
            "cod_vendedor" => $codVendedor,
            "anio" => $anio,
            "mes" => $mes,
            "meta_venta" => self::normalizarMonto(isset($_POST["nuevaMetaVenta"]) ? $_POST["nuevaMetaVenta"] : 0),
            "meta_cobranza" => self::normalizarMetaCobranza(isset($_POST["nuevaMetaCobranza"]) ? $_POST["nuevaMetaCobranza"] : ""),
            "usuario" => isset($_SESSION["id"]) ? (int) $_SESSION["id"] : null,
        );

        $respuesta = ModeloMetasVendedor::mdlIngresarMeta($datos);

        if ($respuesta === "ok") {
            $url = "index.php?ruta=metas-vendedor&anio=" . $anio . "&mes=" . $mes;
            echo '<script>
                swal({
                    type: "success",
                    title: "Meta registrada correctamente",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                }).then(function(result){
                    if (result.value) {
                        window.location = "' . $url . '";
                    }
                });
            </script>';
        }
    }

    static public function ctrEditarMeta()
    {
        if (!isset($_POST["idMeta"])) {
            return;
        }

        $id = (int) $_POST["idMeta"];
        $meta = ModeloMetasVendedor::mdlMostrarMeta("id", $id);

        if (!$meta) {
            echo '<script>swal({type:"error",title:"Meta no encontrada"});</script>';
            return;
        }

        $datos = array(
            "id" => $id,
            "meta_venta" => self::normalizarMonto(isset($_POST["editarMetaVenta"]) ? $_POST["editarMetaVenta"] : 0),
            "meta_cobranza" => self::normalizarMetaCobranza(isset($_POST["editarMetaCobranza"]) ? $_POST["editarMetaCobranza"] : ""),
            "usuario" => isset($_SESSION["id"]) ? (int) $_SESSION["id"] : null,
        );

        $respuesta = ModeloMetasVendedor::mdlEditarMeta($datos);

        if ($respuesta === "ok") {
            $url = "index.php?ruta=metas-vendedor&anio=" . (int) $meta["anio"] . "&mes=" . (int) $meta["mes"];
            echo '<script>
                swal({
                    type: "success",
                    title: "Meta actualizada correctamente",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                }).then(function(result){
                    if (result.value) {
                        window.location = "' . $url . '";
                    }
                });
            </script>';
        }
    }

    static public function ctrEliminarMeta()
    {
        if (!isset($_GET["idMeta"])) {
            return;
        }

        $id = (int) $_GET["idMeta"];
        $meta = ModeloMetasVendedor::mdlMostrarMeta("id", $id);
        $respuesta = ModeloMetasVendedor::mdlEliminarMeta($id);

        if ($respuesta === "ok" && $meta) {
            $url = "index.php?ruta=metas-vendedor&anio=" . (int) $meta["anio"] . "&mes=" . (int) $meta["mes"];
            echo '<script>
                swal({
                    type: "success",
                    title: "Meta eliminada correctamente",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                }).then(function(result){
                    if (result.value) {
                        window.location = "' . $url . '";
                    }
                });
            </script>';
        }
    }
}
