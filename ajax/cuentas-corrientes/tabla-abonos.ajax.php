<?php

require_once "../../controladores/abonos.controlador.php";
require_once "../../modelos/abonos.modelo.php";

header("Content-Type: application/json; charset=utf-8");

class TablaAbonos
{

    /*=============================================
    MOSTRAR LA TABLA DE ABONOS
    =============================================*/

    public function mostrarTablaAbonos()
    {

        $filtroMotivo = isset($_GET["motivo"]) ? trim($_GET["motivo"]) : null;
        if ($filtroMotivo === "") {
            $filtroMotivo = null;
        }

        $anio = isset($_GET["anio"]) ? trim($_GET["anio"]) : null;
        $mes = isset($_GET["mes"]) ? trim($_GET["mes"]) : null;
        if ($anio === "") {
            $anio = null;
        }
        if ($mes === "" || $mes === "todos" || $mes === "0") {
            $mes = null;
        }

        $abono = ControladorAbonos::ctrMostrarAbonos(null, null, $filtroMotivo, $anio, $mes);
        if (!is_array($abono) || count($abono) === 0) {
            echo json_encode(array("data" => array()));
            return;
        }

        $data = array();

        for ($i = 0; $i < count($abono); $i++) {

            $id = (int) $abono[$i]["id"];
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

            $botones = "<div class='btn-group'>"
                . "<button class='btn btn-xs btn-info btnMotivoAbono' idAbono='" . $id . "' title='Motivo / observación' data-toggle='modal' data-target='#modalMotivoAbono'><i class='fa fa-comment'></i></button>"
                . "<button class='btn btn-xs btn-warning btnEditarAbono' idAbono='" . $id . "' data-toggle='modal' data-target='#modalEditarAbono'><i class='fa fa-pencil'></i></button>"
                . "<button class='btn btn-xs btn-danger btnEliminarAbono' idAbono='" . $id . "'><i class='fa fa-times'></i></button>"
                . "</div>";

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
ACTIVAR TABLA DE ABONO
=============================================*/
$activarAbonos = new TablaAbonos();
$activarAbonos->mostrarTablaAbonos();
