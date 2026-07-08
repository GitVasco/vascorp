<?php

require_once "../controladores/metas-vendedor.controlador.php";
require_once "../modelos/metas-vendedor.modelo.php";

class AjaxMetasVendedor
{
    public $idMeta;

    public function ajaxEditarMeta()
    {
        $item = "id";
        $valor = $this->idMeta;
        $respuesta = ControladorMetasVendedor::ctrMostrarMeta($item, $valor);

        echo json_encode($respuesta);
    }
}

if (isset($_POST["idMeta"])) {
    $meta = new AjaxMetasVendedor();
    $meta->idMeta = $_POST["idMeta"];
    $meta->ajaxEditarMeta();
}
