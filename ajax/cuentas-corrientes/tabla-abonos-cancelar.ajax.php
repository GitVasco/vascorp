<?php

require_once "../../controladores/abonos.controlador.php";
require_once "../../modelos/abonos.modelo.php";

header("Content-Type: application/json; charset=utf-8");

class TablaCancelarAbonos
{

    /*=============================================
    MOSTRAR LA TABLA DE ABONOS
    =============================================*/

    public function mostrarTablaCancelarAbonos()
    {

        $filtroMotivo = isset($_GET["motivo"]) ? trim($_GET["motivo"]) : null;
        if ($filtroMotivo === "") {
            $filtroMotivo = null;
        }

        // Cancelar: solo pendientes desde 2026 (sin filtrar mes; se usan todos los del año mínimo+)
        $abono = ControladorAbonos::ctrMostrarAbonos(null, null, $filtroMotivo, null, null);
        if (!is_array($abono) || count($abono) === 0) {
            echo json_encode(array("data" => array()));
            return;
        }

        $data = array();

        for ($i = 0; $i < count($abono); $i++) {

            $id = (int) $abono[$i]["id"];
            $idCuadreReserva = isset($abono[$i]["id_cuadre"]) ? (int) $abono[$i]["id_cuadre"] : 0;
            if ($idCuadreReserva > 0) {
                continue;
            }
            $codigoMotivo = isset($abono[$i]["motivo_pendiente"]) ? $abono[$i]["motivo_pendiente"] : "";
            $etiquetaMotivo = ControladorAbonos::ctrEtiquetaMotivoPendiente($codigoMotivo);

            if ($etiquetaMotivo !== "") {
                $motivoHtml = "<span class='label label-warning'>"
                    . htmlspecialchars($etiquetaMotivo, ENT_QUOTES, "UTF-8")
                    . "</span>";
            } else {
                $motivoHtml = "<span class='text-muted'>—</span>";
            }

            $agenciaHtml = ControladorAbonos::ctrHtmlAgenciaAbono(
                isset($abono[$i]["agencia"]) ? $abono[$i]["agencia"] : ""
            );

            $botones = "<input class='chkAbono' type='checkbox' name='chkAbono' saldo='"
                . htmlspecialchars((string) $abono[$i]["monto"], ENT_QUOTES, "UTF-8")
                . "' idAbono='" . $id
                . "' fecAbono='" . htmlspecialchars((string) $abono[$i]["fecha"], ENT_QUOTES, "UTF-8")
                . "' opAbono='" . htmlspecialchars((string) $abono[$i]["num_ope"], ENT_QUOTES, "UTF-8")
                . "'> Buscar";

            $montoNum = isset($abono[$i]["monto"]) ? (float) $abono[$i]["monto"] : 0;
            $montoHtml = "<div style='text-align:right'>S/. "
                . number_format($montoNum, 2, ".", ",")
                . "</div>";

            $data[] = array(
                isset($abono[$i]["fecha"]) ? $abono[$i]["fecha"] : "",
                isset($abono[$i]["descripcion"]) ? $abono[$i]["descripcion"] : "",
                $montoHtml,
                $agenciaHtml,
                isset($abono[$i]["num_ope"]) ? $abono[$i]["num_ope"] : "",
                $motivoHtml,
                $botones,
            );
        }

        echo json_encode(array("data" => $data), JSON_UNESCAPED_UNICODE);
    }
}

/*=============================================
ACTIVAR TABLA DE CANCELAR ABONO
=============================================*/
$activarCancelarAbonos = new TablaCancelarAbonos();
$activarCancelarAbonos->mostrarTablaCancelarAbonos();
