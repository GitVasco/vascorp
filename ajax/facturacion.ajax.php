<?php
session_start();
require_once "../controladores/facturacion.controlador.php";
require_once "../modelos/facturacion.modelo.php";
require_once "../modelos/pedidos.modelo.php";
require_once "../controladores/pedidos.controlador.php";
require_once '../modelos/usuarios.modelo.php';


class AjaxFacturacion{
    
    /*=============================================
    CREAR DOCUMENTO DE VENTA
    =============================================*/	
      public $datosVenta;
      public function ajaxCrearVentaNota(){
        $valor = $this->datosVenta;
        $datos = json_decode($valor);
        foreach ($datos->{"datosCuenta"} as  $value) {
          $doc = $value->{"tipo_venta"};
          $cta = $value->{"num_cta"};
          $cli = $value->{"cliente"};
          $vend = $value->{"vendedor"};
          $neto = $value->{"neto"};
          $igv = $value->{"igv"};
          $monto = $value->{"monto"};
          

          $fecha = $value->{"fecha"};
          $tipo_doc = $value->{"tip_doc_venta"};
          $origen_venta = $value->{"origen_venta"};
          $tip_nota = $value->{"tip_nota"};
          $motivo = $value->{"motivo"};
          $tip_cont = $value->{"tip_cont"};
          $fecha_origen = $value->{"fecha_origen"};
          $observacion = $value->{"observacion"};
          $user = $value->{"usuario"};

          if($tipo_doc == 'NC'){
            $total= "-".$monto;
            $neto2 = "-".$neto;
            $igv2 = "-".$igv;
          }else{
            $total = $monto;
            $neto2 = $neto;
            $igv2 = $igv;
          }
          $arregloVenta = array("tipo"=>$doc,
                                "documento"=>$cta,
                                "neto"=>$neto2,
                                "igv"=>$igv2,
                                "total"=>$total,
                                "cliente"=>$cli,
                                "vendedor"=>$vend,
                                "fecha"=>$fecha,
                                "tipo_documento"=>$tipo_doc,
                                "doc_origen"=>$origen_venta,
                                "usuario"=>$user);
          
          $respuesta = ModeloFacturacion::mdlRegistrarVentaNota($arregloVenta);

          $arregloNota = array("tipo"=>$doc,
                                "documento"=>$cta,
                                "tipo_doc"=>$tip_nota,
                                "doc_origen"=>$origen_venta,
                                "fecha_origen"=>$fecha_origen,
                                "motivo"=>$motivo,
                                "tip_cont"=>$tip_cont,
                                "observacion"=>$observacion,
                                "usuario"=>$user);

          $respuesta2 = ModeloFacturacion::mdlIngresarNotaCD($arregloNota);
        
          if($tipo_doc == 'NC'){
            $aumento = ModeloCuentas::mdlActualizarNotaSerie("nota_credito","serie_nc",substr($cta,0,4));
          }else{
            $aumento = ModeloCuentas::mdlActualizarNotaSerie("nota_debito","serie_nd",substr($cta,0,4));
          }
          
        }
    
        echo $respuesta;
    
      }

      /*=============================================
      VALIDAR DOCUMENTO DE VENTA EN CREDITO
      =============================================*/	
      public $documentoCredito;
      public function ajaxValidarDocumentoCredito(){
        
        $valor=$this->documentoCredito;
        $tipo="E05";
        $estado="FACTURADO";
        $respuesta=ControladorFacturacion::ctrMostrarTablas($tipo,$estado,$valor);
        echo json_encode($respuesta);
      }
    
      /*=============================================
      VALIDAR DOCUMENTO DE VENTA EN DEBITO
      =============================================*/	
      public $documentoDebito;
      public function ajaxValidarDocumentoDebito(){
        
        $valor=$this->documentoDebito;
        $tipo="E23";
        $estado="FACTURADO";
        $respuesta=ControladorFacturacion::ctrMostrarTablas($tipo,$estado,$valor);
        echo json_encode($respuesta);
      }
    
      /*=============================================
      EDITAR DOCUMENTO DE VENTA
      =============================================*/	
      public $datosVenta2;
      public function ajaxEditarVentaNota(){
        $valor = $this->datosVenta2;
        $datos = json_decode($valor);
        foreach ($datos->{"datosCuenta"} as  $value) {
          $doc = $value->{"tipo_venta"};
          $cta = $value->{"num_cta"};
          $cli = $value->{"cliente"};
          $vend = $value->{"vendedor"};
          $neto = $value->{"neto"};
          $igv = $value->{"igv"};
          $monto = $value->{"monto"};
          $fecha = $value->{"fecha"};
          $origen_venta = $value->{"origen_venta"};
          $tip_nota = $value->{"tip_nota"};
          $motivo = $value->{"motivo"};
          $tip_cont = $value->{"tip_cont"};
          $fecha_origen = $value->{"fecha_origen"};
          $observacion = $value->{"observacion"};
          $user = $value->{"usuario"};

          if($doc == 'E05'){
            $total= "-".$monto;
            $neto2 = "-".$neto;
            $igv2 = "-".$igv;
          }else{
            $total = $monto;
            $neto2 = $neto;
            $igv2 = $igv;
          }
          $arregloVenta = array("tipo"=>$doc,
                                "documento"=>$cta,
                                "neto"=>$neto2,
                                "igv"=>$igv2,
                                "total"=>$total,
                                "cliente"=>$cli,
                                "vendedor"=>$vend,
                                "fecha"=>$fecha,
                                "doc_origen"=>$origen_venta,
                                "usuario"=>$user);
          
        $respuesta = ModeloFacturacion::mdlEditarVentaNota($arregloVenta);

        $arregloNota = array("tipo"=>$doc,
                                "documento"=>$cta,
                                "tipo_doc"=>$tip_nota,
                                "doc_origen"=>$origen_venta,
                                "fecha_origen"=>$fecha_origen,
                                "motivo"=>$motivo,
                                "tip_cont"=>$tip_cont,
                                "observacion"=>$observacion,
                                "usuario"=>$user);

          $respuesta2 = ModeloFacturacion::mdlEditarNotaCD($arregloNota);
        }
        
    
        echo $respuesta2;
    
      }

      /*=============================================
      ACTIVAR PEDIDO
      =============================================*/	
      public function ajaxActivarPedido(){
        
        $valor=$this->activarId;
        $estado= $this->activarEstado;
        $usuario=$_SESSION["id"];
        $nom_user = $_SESSION["nombre"];
        date_default_timezone_set('America/Lima');
		    $fecha = new DateTime();
        if($estado == 'APROBADO'){

          $descripcion   = 'El usuario '.$nom_user.' aprobó el pedido '.$valor;
          $detalle = ControladorPedidos::ctrMostrarDetallesTemporal($valor);
          foreach ($detalle as $key => $value) {
            $articulo = ModeloFacturacion::mdlActualizarArticuloPedido($value["articulo"],$value["cantidad"]);
          }

        }else if($estado == 'APT'){
          
          $descripcion   = 'El usuario '.$nom_user.' dio de apta el pedido '.$valor;

        }else if($estado == 'CONFIRMADO'){

          $temporal= ControladorPedidos::ctrMostrarTemporal($valor);
          $tabla="temporaljf_bkp";
          $datos1= array("codigo" => $temporal["codigo"],
          "cliente" => $temporal["cliente"],
          "vendedor" => $temporal["vendedor"],
          "lista" => $temporal["lista"],
          "op_gravada" => $temporal["op_gravada"],
          "descuento_total" => $temporal["descuento_total"],
          "sub_total" => $temporal["sub_total"],
          "igv" => $temporal["igv"],
          "total" => $temporal["total"],
          "condicion_venta" => $temporal["condicion_venta"],
          "estado" => $temporal["estado"],
          "fecha" => $temporal["fecha"],
          "usuario" => $temporal["usuario"],
          "agencia" => $temporal["agencia"],
          "usuario_estado" => $temporal["usuario_estado"]);

          ModeloPedidos::mdlGuardarTemporalBkp($tabla, $datos1);


          $detalle = ControladorPedidos::ctrMostrarDetallesTemporal($valor);
          foreach ($detalle as $key => $value) {
            $tabla2="detalle_temporalbkp";
            $datos3 = array( "codigo"    => $value["codigo"],
                            "articulo"  => $value["articulo"],
                            "cantidad"  => $value["cantidad"],
                            "precio"    => $value["precio"]);

                        $respuesta = ModeloPedidos::mdlGuardarTemporalDetalle($tabla2, $datos3);
          }

          $descripcion   = 'El usuario '.$nom_user.' confirmo el pedido '.$valor;

        }else if($estado == 'FACTURADOS'){

          $descripcion   = 'El usuario '.$nom_user.' facturo el pedido '.$valor;

        }

        if($_SESSION["datos"] == 1){
          $datos2= array( "usuario" => $nom_user,
              "concepto" => $descripcion,
              "fecha" => $fecha->format("Y-m-d H:i:s"));
          $auditoria=ModeloUsuarios::mdlIngresarAuditoria("auditoriajf",$datos2);
        }
        $respuesta=ModeloFacturacion::mdlActualizarPedido($valor,$estado,$usuario);
        echo $respuesta;
      }

    }
    

  /*=============================================
    CREAR VENTA
    =============================================*/	
    if(isset($_POST["jsonCuenta"])){
      
      $crearVenta = new AjaxFacturacion();
      $crearVenta -> datosVenta = $_POST["jsonCuenta"];
      $crearVenta -> ajaxCrearVentaNota();
  }


    /*=============================================
    VALIDAR VENTA CREDITO
    =============================================*/	
    if(isset($_POST["documentoCredito"])){
        $validarDocumentoCredito=new AjaxFacturacion();
        $validarDocumentoCredito->documentoCredito=$_POST["documentoCredito"];
        $validarDocumentoCredito->ajaxValidarDocumentoCredito();
    }

    /*=============================================
    VALIDAR VENTA DEBITO
    =============================================*/	
    if(isset($_POST["documentoDebito"])){
        $validarDocumentoDebito=new AjaxFacturacion();
        $validarDocumentoDebito->documentoDebito=$_POST["documentoDebito"];
        $validarDocumentoDebito->ajaxValidarDocumentoDebito();
    }
    
  /*=============================================
    EDITAR VENTA
    =============================================*/	
    if(isset($_POST["jsonCuenta2"])){
      
      $editarVenta = new AjaxFacturacion();
      $editarVenta -> datosVenta2 = $_POST["jsonCuenta2"];
      $editarVenta -> ajaxEditarVentaNota();
  }

   /*=============================================
    ACTIVAR PEDIDOS
    =============================================*/	
    if(isset($_POST["activarEstado"])){
      $activarPedido=new AjaxFacturacion();
      $activarPedido->activarEstado=$_POST["activarEstado"];
      $activarPedido->activarId=$_POST["activarId"];
      $activarPedido->ajaxActivarPedido();
  }