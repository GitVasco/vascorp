<?php

if (!isset($_SESSION)) {
    session_start();
}

require_once "../../controladores/descuentos-compuestos.controlador.php";
require_once "../../modelos/descuentos-compuestos.modelo.php";

class AjaxDescuentosCompuestos
{
    public $accion;
    public $id;
    public $notaEstandar;
    public $observacion;
    public $estado;

    public function ejecutar()
    {
        switch ($this->accion) {
            case "obtener":
                $this->obtener();
                break;
            case "guardar":
                $this->guardar();
                break;
            case "confirmar":
                $this->confirmar();
                break;
            case "descartar":
                $this->descartar();
                break;
            case "restaurar":
                $this->restaurar();
                break;
            default:
                echo json_encode(array("ok" => false, "mensaje" => "Acción no válida."));
                break;
        }
    }

    private function obtener()
    {
        $registro = ControladorDescuentosCompuestos::ctrObtenerDescuentoCompuesto($this->id);

        if ($registro === null) {
            echo json_encode(array("ok" => false, "mensaje" => "Registro no encontrado."));
            return;
        }

        echo json_encode(array("ok" => true, "registro" => $registro));
    }

    private function guardar()
    {
        $resultado = ControladorDescuentosCompuestos::ctrGuardarCorreccion(array(
            "id" => $this->id,
            "nota_estandar" => $this->notaEstandar,
            "observacion" => $this->observacion,
            "estado" => $this->estado,
        ));

        if ($resultado === "ok") {
            echo json_encode(array("ok" => true, "mensaje" => "Corrección guardada."));
        } else {
            echo json_encode(array("ok" => false, "mensaje" => $resultado));
        }
    }

    private function confirmar()
    {
        $resultado = ControladorDescuentosCompuestos::ctrConfirmarPropuesta($this->id);

        if ($resultado === "ok") {
            echo json_encode(array("ok" => true, "mensaje" => "Sugerencia confirmada."));
        } else {
            echo json_encode(array("ok" => false, "mensaje" => $resultado));
        }
    }

    private function descartar()
    {
        $resultado = ControladorDescuentosCompuestos::ctrDescartar($this->id);

        if ($resultado === "ok") {
            echo json_encode(array("ok" => true, "mensaje" => "Registro descartado."));
        } else {
            echo json_encode(array("ok" => false, "mensaje" => $resultado));
        }
    }

    private function restaurar()
    {
        $resultado = ControladorDescuentosCompuestos::ctrRestaurar($this->id);

        if ($resultado === "ok") {
            echo json_encode(array("ok" => true, "mensaje" => "Registro restaurado."));
        } else {
            echo json_encode(array("ok" => false, "mensaje" => $resultado));
        }
    }
}

$peticion = new AjaxDescuentosCompuestos();
$peticion->accion = isset($_POST["accion"]) ? $_POST["accion"] : "";
$peticion->id = isset($_POST["id"]) ? $_POST["id"] : 0;
$peticion->notaEstandar = isset($_POST["nota_estandar"]) ? $_POST["nota_estandar"] : "";
$peticion->observacion = isset($_POST["observacion"]) ? $_POST["observacion"] : "";
$peticion->estado = isset($_POST["estado"]) ? $_POST["estado"] : "CONFIRMADO";
$peticion->ejecutar();
