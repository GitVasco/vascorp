<?php

require_once "../../controladores/cuentas.controlador.php";
require_once "../../modelos/cuentas.modelo.php";

class TablaEnvioCuentas
{
    /*=============================================
    MOSTRAR LA TABLA DE CuentaS
    =============================================*/
    public function mostrarTablaEnvioCuentas()
    {
        header("Content-Type: application/json; charset=utf-8");

        $fechaInicial = isset($_GET["fechaInicial"]) ? $_GET["fechaInicial"] : "null";
        $fechaFinal = isset($_GET["fechaFinal"]) ? $_GET["fechaFinal"] : "null";

        $Cuenta = ControladorCuentas::ctrRangoFechaEnvioCuentas($fechaInicial, $fechaFinal);
        if (!is_array($Cuenta) || count($Cuenta) === 0) {
            echo json_encode(array("data" => array()));
            return;
        }

        $data = array();
        for ($i = 0; $i < count($Cuenta); $i++) {
            $archivo = isset($Cuenta[$i]["archivo"]) ? (string) $Cuenta[$i]["archivo"] : "";
            $extArchivo = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
            $formatoActual = ($extArchivo === "xls" || $extArchivo === "xlsx")
                ? "excel"
                : ($archivo !== "" ? "txt" : "");

            if ($archivo !== "") {
                $badgeFormato = $formatoActual === "excel"
                    ? "<span class='label label-info' title='Archivo actual'>Excel</span>"
                    : "<span class='label label-default' title='Archivo actual'>TXT</span>";
            } else {
                $badgeFormato = "<span class='label label-warning' title='Sin archivo'>Sin archivo</span>";
            }

            $idEnvio = (int) $Cuenta[$i]["id"];
            $archivoAttr = htmlspecialchars($archivo, ENT_QUOTES, "UTF-8");
            $extTxtOk = ($extArchivo === "txt") ? $archivoAttr : "";
            $extXlsxOk = ($extArchivo === "xlsx") ? $archivoAttr : "";

            $botones = "<div class='btn-group'>"
                . "<button type='button' class='btn btn-xs btn-info btnVisualizarEnvioCuenta' idEnvio='" . $idEnvio . "' title='Ver detalle'><i class='fa fa-eye'></i></button>"
                . "<button type='button' class='btn btn-xs btn-warning btnEditarEnvioLetra' idEnvio='" . $idEnvio . "' title='Editar envío'><i class='fa fa-pencil'></i></button>"
                . "<button type='button' class='btn btn-xs btn-success dropdown-toggle' data-toggle='dropdown' title='Descargar planilla'>"
                . "<i class='fa fa-download'></i> <span class='caret'></span>"
                . "</button>"
                . "<ul class='dropdown-menu dropdown-menu-right'>"
                . "<li><a href='#' class='btnDescargarEnvioFormato' idEnvio='" . $idEnvio . "' data-formato='txt' data-archivo='" . $extTxtOk . "' data-formato-actual='" . ($extArchivo === "txt" ? "txt" : "") . "'><i class='fa fa-file-text-o'></i> TXT (vigente)</a></li>"
                . "<li><a href='#' class='btnDescargarEnvioFormato' idEnvio='" . $idEnvio . "' data-formato='excel' data-archivo='" . $extXlsxOk . "' data-formato-actual='" . ($extArchivo === "xlsx" ? "excel" : "") . "'><i class='fa fa-file-excel-o'></i> Excel (.xlsx)</a></li>"
                . "</ul>"
                . "<button type='button' class='btn btn-xs btn-danger btnAnularEnvioCuenta' idEnvio='" . $idEnvio . "' title='Anular envío'><i class='fa fa-times'></i></button>"
                . "</div>";

            $data[] = array(
                isset($Cuenta[$i]["codigo"]) ? (string) $Cuenta[$i]["codigo"] : "",
                isset($Cuenta[$i]["fecha"]) ? (string) $Cuenta[$i]["fecha"] : "",
                isset($Cuenta[$i]["nombre"]) ? (string) $Cuenta[$i]["nombre"] : "",
                isset($Cuenta[$i]["cantidad"]) ? $Cuenta[$i]["cantidad"] : "",
                $badgeFormato,
                $botones
            );
        }

        echo json_encode(array("data" => $data));
    }
}

/*=============================================
ACTIVAR TABLA DE Cuenta
=============================================*/
$activarEnvioCuentas = new TablaEnvioCuentas();
$activarEnvioCuentas->mostrarTablaEnvioCuentas();
