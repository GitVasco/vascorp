<?php

require_once "../controladores/vendedor.controlador.php";
require_once "../modelos/vendedor.modelo.php";


class AjaxVendedores{
    /*=============================================
      EDITAR VENDEDOR
      =============================================*/ 
    
      public $idVendedor;
      public $estadoDecisiones;
    
      public function ajaxEditarVendedor(){
        $item="id";
        $valor = $this->idVendedor;
    
        $respuesta = ControladorVendedores::ctrMostrarVendedores($item,$valor);
    
        echo json_encode($respuesta);
    
      }

      public function ajaxToggleEstadoDecisiones(){

        $respuesta = ControladorVendedores::ctrToggleEstadoDecisiones(
            $this->idVendedor,
            $this->estadoDecisiones
        );

        if($respuesta === "ok"){
            echo json_encode(array(
                "status" => "ok",
                "estado_decisiones" => (int) $this->estadoDecisiones
            ));
            return;
        }

        echo json_encode(array("status" => "error"));

      }
    
    }
    
    
    /*=============================================
    TOGGLE CENTRO DE DECISIONES
    =============================================*/	
    if(isset($_POST["toggleEstadoDecisiones"])){

        $vendedor = new AjaxVendedores();
        $vendedor->idVendedor = $_POST["idVendedor"];
        $vendedor->estadoDecisiones = isset($_POST["estadoDecisiones"]) ? (int) $_POST["estadoDecisiones"] : 0;
        $vendedor->ajaxToggleEstadoDecisiones();

    }else if(isset($_POST["idVendedor"])){

    /*=============================================
    EDITAR VENDEDOR
    =============================================*/	
        $tipoPago = new AjaxVendedores();
        $tipoPago -> idVendedor = $_POST["idVendedor"];
        $tipoPago -> ajaxEditarVendedor();
    }
    