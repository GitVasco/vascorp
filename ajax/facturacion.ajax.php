<?php
session_start();
require_once "../controladores/facturacion.controlador.php";
require_once "../modelos/facturacion.modelo.php";
require_once "../modelos/pedidos.modelo.php";
require_once "../controladores/pedidos.controlador.php";
require_once '../modelos/usuarios.modelo.php';


class AjaxFacturacion
{

    /*=============================================
     VER SI TIENE IMAGEN
      =============================================*/
    public function ajaxVerDocumento()
    {

        $tipo = $this->tipoI;
        $documento = $this->documentoI;

        $respuesta = ControladorFacturacion::ctrVerDocumento($tipo, $documento);
        echo json_encode($respuesta);
    }

    /*=============================================
    CREAR DOCUMENTO DE VENTA
    =============================================*/
    public $datosVenta;
    public function ajaxCrearVentaNota()
    {
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

            if ($tipo_doc == 'NC') {
                $total = "-" . $monto;
                $neto2 = "-" . $neto;
                $igv2 = "-" . $igv;
            } else {
                $total = $monto;
                $neto2 = $neto;
                $igv2 = $igv;
            }

            $usureg = $_SESSION["nombre"];
            $pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);


            $arregloVenta = array(
                "tipo" => $doc,
                "documento" => $cta,
                "neto" => $neto2,
                "igv" => $igv2,
                "total" => $total,
                "cliente" => $cli,
                "vendedor" => $vend,
                "fecha" => $fecha,
                "tipo_documento" => $tipo_doc,
                "doc_origen" => '',
                "usuario" => $user,
                "usureg" => $usureg,
                "pcreg" => $pcreg
            );

            $respuesta = ModeloFacturacion::mdlRegistrarVentaNota($arregloVenta);

            $arregloNota = array(
                "tipo" => $doc,
                "documento" => $cta,
                "tipo_doc" => $tip_nota,
                "doc_origen" => $origen_venta,
                "fecha_origen" => $fecha_origen,
                "motivo" => $motivo,
                "tip_cont" => $tip_cont,
                "observacion" => $observacion,
                "usuario" => $user
            );

            $respuesta2 = ModeloFacturacion::mdlIngresarNotaCD($arregloNota);

            if ($tipo_doc == 'NC') {
                $aumento = ModeloFacturacion::mdlActualizarNotaSerie("nota_credito", "serie_nc", substr($cta, 0, 4));
            } else {
                $aumento = ModeloFacturacion::mdlActualizarNotaSerie("nota_debito", "serie_nd", substr($cta, 0, 4));
            }
        }

        echo $respuesta2;
    }

    /*=============================================
      VALIDAR DOCUMENTO DE VENTA EN CREDITO
      =============================================*/
    public $documentoCredito;
    public function ajaxValidarDocumentoCredito()
    {

        $valor = $this->documentoCredito;
        $tipo = "E05";
        // Sin filtrar por estado: si está ENVIADO/GENERADO/etc. debe editar, no insertar de nuevo
        $respuesta = ControladorFacturacion::ctrVerDocumento($tipo, $valor);
        echo json_encode($respuesta);
    }

    /*=============================================
      VALIDAR DOCUMENTO DE VENTA EN DEBITO
      =============================================*/
    public $documentoDebito;
    public function ajaxValidarDocumentoDebito()
    {

        $valor = $this->documentoDebito;
        $tipo = "S05";
        // Sin filtrar por estado: si está ENVIADO/GENERADO/etc. debe editar, no insertar de nuevo
        $respuesta = ControladorFacturacion::ctrVerDocumento($tipo, $valor);
        echo json_encode($respuesta);
    }

    /*=============================================
      EDITAR DOCUMENTO DE VENTA
      =============================================*/
    public $datosVenta2;
    public function ajaxEditarVentaNota()
    {
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

            if ($doc == 'E05') {
                $total = "-" . $monto;
                $neto2 = "-" . $neto;
                $igv2 = "-" . $igv;
            } else {
                $total = $monto;
                $neto2 = $neto;
                $igv2 = $igv;
            }
            $arregloVenta = array(
                "tipo" => $doc,
                "documento" => $cta,
                "neto" => $neto2,
                "igv" => $igv2,
                "total" => $total,
                "cliente" => $cli,
                "vendedor" => $vend,
                "fecha" => $fecha,
                "doc_origen" => $origen_venta,
                "usuario" => $user
            );

            $respuesta = ModeloFacturacion::mdlEditarVentaNota($arregloVenta);

            $arregloNota = array(
                "tipo" => $doc,
                "documento" => $cta,
                "tipo_doc" => $tip_nota,
                "doc_origen" => $origen_venta,
                "fecha_origen" => $fecha_origen,
                "motivo" => $motivo,
                "tip_cont" => $tip_cont,
                "observacion" => $observacion,
                "usuario" => $user
            );

            $respuesta2 = ModeloFacturacion::mdlEditarNotaCD($arregloNota);
        }


        echo $respuesta2;
    }

    /*=============================================
    VERIFICAR BLOQUEO POR CONTROL POST-APROBACIÓN (facturación)
    =============================================*/
    public $codigoPedidoControl;

    public function ajaxVerificarControlFacturacion()
    {
        require_once "../controladores/decisiones-credito.config.php";
        require_once "../modelos/decisiones-credito.modelo.php";

        $codigo = (int) $this->codigoPedidoControl;
        $bloqueo = null;

        if ($codigo > 0 && class_exists("ModeloDecisionesCredito")) {
            $bloqueo = ModeloDecisionesCredito::mdlControlBloqueantePendiente($codigo);
        }

        echo json_encode(array(
            "ok" => true,
            "bloqueado" => $bloqueo !== null,
            "msg" => $bloqueo ? $bloqueo["mensaje"] : "",
        ));
    }

    /*=============================================
    ACTIVAR PEDIDO
    =============================================*/
    public function ajaxActivarPedido()
    {

        $valor = $this->activarId;
        $estado = $this->activarEstado;

        $usuario = $_SESSION["id"];
        $nom_user = $_SESSION["nombre"];
        date_default_timezone_set('America/Lima');
        $fecha = new DateTime();

        $respuesta = ModeloFacturacion::mdlActualizarPedido($valor, $estado, $usuario);

        ModeloPedidos::mdlCantAprobados();

        if ($estado == 'APROBADO') {

            $descripcion   = 'El usuario ' . $nom_user . ' aprobó el pedido ' . $valor;
        } else if ($estado == 'APT') {

            $descripcion   = 'El usuario ' . $nom_user . ' dio de apta el pedido ' . $valor;
        } else if ($estado == 'CONFIRMADO') {

            ModeloPedidos::mdlGuardarTemporalBkpCab($valor);

            ModeloPedidos::mdlGuardarTemporalBkpDet($valor);

            $descripcion   = 'El usuario ' . $nom_user . ' confirmo el pedido ' . $valor;
        }

        if ($_SESSION["datos"] == 1) {
            $datos2 = array(
                "usuario" => $nom_user,
                "concepto" => $descripcion,
                "fecha" => $fecha->format("Y-m-d H:i:s")
            );
            $auditoria = ModeloUsuarios::mdlIngresarAuditoria("auditoriajf", $datos2);
        }


        echo $respuesta;
    }

    /*=============================================
    ACTIVAR PEDIDO
    =============================================*/
    public function ajaxActualizarTalonariGuia()
    {

        $guia = $this->guia;

        $respuesta = ModeloFacturacion::mdlActualizarTalonariGuia($guia);

        echo json_encode($respuesta);
    }

    /*=============================================
      GENERAR TOKEN PARA HACER CONSULTAS SUNAT
      =============================================*/

    public function ajaxGenerarTokenSunat()
    {


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api-seguridad.sunat.gob.pe/v1/clientesextranet/af1e8535-d99a-4915-b515-91e36d9f71ae/oauth2/token/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials&scope=https%3A%2F%2Fapi.sunat.gob.pe%2Fv1%2Fcontribuyente%2Fcontribuyentes&client_id=af1e8535-d99a-4915-b515-91e36d9f71ae&client_secret=MepGYmNzOeZ6EMMr2i0t4A%3D%3D',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: TS018412f9=019edc9eb834ae85d7ac809fca3c15c40ba0156fd2663004ad2f190436eb4c8324d48b0b054d4736900b03feb2b2905c6f44c1420f; TS019e7fc2=019edc9eb8f3cea6da929c599af337f8af0655e4e3a6f2eff26c162aff3187235d847ab4490f8a5525d82221ecc76edcc6a334f6eb'
            ),
        ));
        curl_setopt($curl, CURLOPT_CAINFO, "C:/xampp/htdocs/vascorp/vistas/generar_xml/cacert.pem");

        $response = curl_exec($curl);
        //$error = curl_error($curl);

        curl_close($curl);
        echo $response;
    }

    /*=============================================
      GUARDAR TOKEN PARA HACER CONSULTAS SUNAT
      =============================================*/

    public function ajaxGuardarTokenSunat()
    {

        $valor = $this->tokenSunat;
        $valor2 = $this->tiempoToken;
        $respuesta = ModeloFacturacion::mdlActualizarToken($valor, $valor2);

        echo $respuesta;
    }

    /*=============================================
      CONSULTAR SUNAT COMPROBANTES ELECTRONICOS
      =============================================*/

    public function ajaxConsultarSunat()
    {

        $tipo = $this->tipoConsulta;
        $ruc = $this->rucConsulta;
        $serie = $this->serieConsulta;
        $correlativo = $this->correlativoConsulta;
        $emision = $this->emisionConsulta;
        $emisionFormato = date("d/m/Y", strtotime($emision));
        $monto = $this->montoConsulta;

        $respuesta = ModeloFacturacion::mdlConsultarToken();
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.sunat.gob.pe/v1/contribuyente/contribuyentes/10472810371/validarcomprobante',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
            "numRuc": "' . $ruc . '",
            "codComp": "' . $tipo . '",
            "numeroSerie": "' . $serie . '",
            "numero": "' . $correlativo . '",
            "fechaEmision": "' . $emisionFormato . '",
            "monto": "' . $monto . '"
        }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $respuesta["token"] . '',
                'Content-Type: application/json'
            ),
        ));
        curl_setopt($curl, CURLOPT_CAINFO, "C:/xampp/htdocs/vascorp/vistas/generar_xml/cacert.pem");
        $response = curl_exec($curl);
        // $error = curl_error($curl);
        curl_close($curl);
        //demorar medio microsegundo
        usleep(10000);
        echo $response;
    }

    /*=============================================
      VISUALIZAR TOKEN PARA HACER CONSULTAS SUNAT
      =============================================*/

    public function ajaxVisualizarTokenSunat()
    {
        $respuesta = ModeloFacturacion::mdlConsultarToken();
        echo json_encode($respuesta);
    }

    //*GENERAR CSV
    public function ajaxGenerarFEFacBol()
    {

        $tipo = $this->tipo;
        $documento = $this->documento;

        if ($tipo == "S02"  || $tipo == "S03") {

            // validamos si el $documento empieza con FX
            if (strpos($documento, 'FX') === 0) {
                // El documento comienza con 'FX'
                $respuesta = ControladorFacturacion::ctrGenerarFEFacBolB($tipo, $documento);
            } else {
                // El documento no comienza con 'FX'
                $respuesta = ControladorFacturacion::ctrGenerarFEFacBolA($tipo, $documento);
            }
            ModeloFacturacion::mdlEnviarTXT($tipo, $documento);
        } elseif ($tipo == "E05") {

            $respuesta = ControladorFacturacion::ctrGenerarFENCA($tipo, $documento);
            ModeloFacturacion::mdlEnviarTXT($tipo, $documento);
        } elseif ($tipo == "S05") {

            $respuesta = ControladorFacturacion::ctrGenerarFENDA($tipo, $documento);
            ModeloFacturacion::mdlEnviarTXT($tipo, $documento);
        } elseif ($tipo == "S01") {

            $respuesta = ControladorFacturacion::ctrGenerarGuia($tipo, $documento);
            ModeloFacturacion::mdlEnviarTXT($tipo, $documento);
        }

        echo $respuesta;
    }

    //*CORREGIR ERRORES
    public function ajaxCorregir()
    {

        $tipo = $this->tipoC;
        $documento = $this->documentoC;
        $neto = $this->netoC;

        $igv = round($neto * 0.18, 2);
        $total = round($neto + $igv, 2);

        if ($tipo == "S02") {

            $tipoCte = "03";
        } else if ($tipo == "S03") {
            $tipoCte = "01";
        } else if ($tipo == "E05") {
            $tipoCte = "07";
        } else if ($tipo == "S05") {
            $tipoCte = "08";
        }

        $respuestaA = ModeloFacturacion::mdlCorregirVenta($tipo, $documento, $neto, $igv, $total);

        $respuestaB = ModeloFacturacion::mdlCorregirCuenta($tipoCte, $documento, $total);

        if ($respuestaA == "ok" && $respuestaB == "ok") {
            $respuesta = "ok";
        } else {
            $respuesta = "no";
        }


        echo json_encode($respuesta);
    }

    public function ajaxTotalCuadre()
    {

        $fechaCuadre = $this->fechaCuadre;

        $respuesta = ModeloFacturacion::mdlTotalCuadre($fechaCuadre);

        echo json_encode($respuesta);
    }

    public function ajaxVerGuia()
    {

        $documentoG = $this->documentoG;

        $respuesta = ModeloFacturacion::mdlMostrarVentaImpresion($documentoG, "S01");

        echo json_encode($respuesta);
    }

    public function ajaxBuscarDocRelacionarGuia()
    {
        $documento = $this->documentoBuscarRel;
        $respuesta = ModeloFacturacion::mdlBuscarFacturaBoleta($documento);

        if (!$respuesta) {
            echo json_encode(array("ok" => false, "mensaje" => "Documento no encontrado"));
            return;
        }

        $meta = ModeloFacturacion::mdlMetaDocRelacionado($respuesta["documento"], $respuesta["tipo"]);
        echo json_encode(array(
            "ok" => true,
            "documento" => $respuesta["documento"],
            "formato" => $meta ? $meta["formato"] : $respuesta["documento"],
            "tipo" => $respuesta["tipo"],
            "nombre_tipo" => $meta ? $meta["nombre"] : "",
            "total" => $respuesta["total"],
            "cliente" => $respuesta["cliente"],
            "nombre" => $respuesta["nombre"],
            "fecha" => $respuesta["fecha"],
            "doc_origen" => $respuesta["doc_origen"]
        ));
    }

    public function ajaxRelacionarDocumentoGuia()
    {
        $documentos = array();

        if (!empty($this->documentosRelacionar)) {
            $decoded = json_decode($this->documentosRelacionar, true);
            if (is_array($decoded)) {
                $documentos = $decoded;
            }
        } elseif (!empty($this->documentoRelacionar)) {
            $documentos = array($this->documentoRelacionar);
        }

        if (count($documentos) > 1 || (!empty($this->documentosRelacionar) && is_array($documentos))) {
            $respuesta = ControladorFacturacion::ctrRelacionarDocumentosGuia(
                $this->guiaRelacionar,
                $documentos
            );
        } else {
            $respuesta = ControladorFacturacion::ctrRelacionarDocumentoGuia(
                $this->guiaRelacionar,
                isset($documentos[0]) ? $documentos[0] : ""
            );
        }

        echo json_encode($respuesta);
    }
}


/*=============================================
    CREAR VENTA
    =============================================*/
if (isset($_POST["jsonCuenta"])) {

    $crearVenta = new AjaxFacturacion();
    $crearVenta->datosVenta = $_POST["jsonCuenta"];
    $crearVenta->ajaxCrearVentaNota();
}


/*=============================================
    VALIDAR VENTA CREDITO
    =============================================*/
if (isset($_POST["documentoCredito"])) {
    $validarDocumentoCredito = new AjaxFacturacion();
    $validarDocumentoCredito->documentoCredito = $_POST["documentoCredito"];
    $validarDocumentoCredito->ajaxValidarDocumentoCredito();
}

/*=============================================
    VALIDAR VENTA DEBITO
    =============================================*/
if (isset($_POST["documentoDebito"])) {
    $validarDocumentoDebito = new AjaxFacturacion();
    $validarDocumentoDebito->documentoDebito = $_POST["documentoDebito"];
    $validarDocumentoDebito->ajaxValidarDocumentoDebito();
}

/*=============================================
    EDITAR VENTA
    =============================================*/
if (isset($_POST["jsonCuenta2"])) {

    $editarVenta = new AjaxFacturacion();
    $editarVenta->datosVenta2 = $_POST["jsonCuenta2"];
    $editarVenta->ajaxEditarVentaNota();
}

/*=============================================
    VERIFICAR CONTROL ANTES DE FACTURAR
    =============================================*/
if (isset($_POST["verificarControlFacturacion"])) {
    $verificarControl = new AjaxFacturacion();
    $verificarControl->codigoPedidoControl = isset($_POST["codigoPedido"]) ? $_POST["codigoPedido"] : 0;
    $verificarControl->ajaxVerificarControlFacturacion();
}

/*=============================================
    ACTIVAR PEDIDOS
    =============================================*/
if (isset($_POST["activarEstado"])) {
    $activarPedido = new AjaxFacturacion();
    $activarPedido->activarEstado = $_POST["activarEstado"];
    $activarPedido->activarId = $_POST["activarId"];
    $activarPedido->ajaxActivarPedido();
}

/*=============================================
    GENERAR TOKEN SUNAT
    =============================================*/
if (isset($_POST["envioToken"])) {

    $generarToken = new AjaxFacturacion();
    $generarToken->ajaxGenerarTokenSunat();
}

/*=============================================
    GUARDAR TOKEN SUNAT
    =============================================*/
if (isset($_POST["guardarToken"])) {

    $guardadoToken = new AjaxFacturacion();
    $guardadoToken->tokenSunat = $_POST["guardarToken"];
    $guardadoToken->tiempoToken = $_POST["tiempoToken"];
    $guardadoToken->ajaxGuardarTokenSunat();
}

/*=============================================
    CONSULTAR  SUNAT
    =============================================*/
if (isset($_POST["tipoConsulta"])) {

    $consultaSunat = new AjaxFacturacion();
    $consultaSunat->tipoConsulta = $_POST["tipoConsulta"];
    $consultaSunat->rucConsulta = $_POST["rucConsulta"];
    $consultaSunat->serieConsulta = $_POST["serieConsulta"];
    $consultaSunat->correlativoConsulta = $_POST["correlativoConsulta"];
    $consultaSunat->emisionConsulta = $_POST["emisionConsulta"];
    $consultaSunat->montoConsulta = $_POST["montoConsulta"];
    $consultaSunat->ajaxConsultarSunat();
}

/*=============================================
  VER TOKEN SUNAT
  =============================================*/
if (isset($_POST["verToken"])) {

    $visualizarToken = new AjaxFacturacion();
    $visualizarToken->ajaxVisualizarTokenSunat();
}

/*=============================================
  GENERAR CSV
  =============================================*/
if (isset($_POST["tipo"])) {

    $generarcsv = new AjaxFacturacion();
    $generarcsv->tipo = $_POST["tipo"];
    $generarcsv->documento = $_POST["documento"];
    $generarcsv->ajaxGenerarFEFacBol();
}


/*=============================================
VER SI TRAE IMAGENES
  =============================================*/
if (isset($_POST["tipoI"])) {

    $generarcsv = new AjaxFacturacion();
    $generarcsv->tipoI = $_POST["tipoI"];
    $generarcsv->documentoI = $_POST["documentoI"];
    $generarcsv->ajaxVerDocumento();
}


/*=============================================
  ACTUALIZAR TALONARIO DE GUIAS
    =============================================*/
if (isset($_POST["guia"])) {

    $guia = new AjaxFacturacion();
    $guia->guia = $_POST["guia"];
    $guia->ajaxActualizarTalonariGuia();
}


/*=============================================
corregir errores
=============================================*/
if (isset($_POST["tipoC"])) {

    $activar = new AjaxFacturacion();
    $activar->tipoC = $_POST["tipoC"];
    $activar->documentoC = $_POST["documentoC"];
    $activar->netoC = $_POST["netoC"];
    $activar->ajaxCorregir();
}


/*=============================================
VER TOTALES
=============================================*/
if (isset($_POST["fechaCuadre"])) {

    $cancelaCuenta = new AjaxFacturacion();
    $cancelaCuenta->fechaCuadre = $_POST["fechaCuadre"];
    $cancelaCuenta->ajaxTotalCuadre();
}



/*=============================================
ACTUALIZAR GUIA
=============================================*/
if (isset($_POST["documentoG"])) {

    $cancelaCuenta = new AjaxFacturacion();
    $cancelaCuenta->documentoG = $_POST["documentoG"];
    $cancelaCuenta->ajaxVerGuia();
}

/*=============================================
BUSCAR FACTURA/BOLETA PARA RELACIONAR A GUIA
=============================================*/
if (isset($_POST["buscarDocRelGuia"])) {
    $ajax = new AjaxFacturacion();
    $ajax->documentoBuscarRel = $_POST["buscarDocRelGuia"];
    $ajax->ajaxBuscarDocRelacionarGuia();
}

/*=============================================
RELACIONAR DOCUMENTO A GUIA GENERADO
=============================================*/
if (isset($_POST["guiaRelacionar"]) && (isset($_POST["documentoRelacionar"]) || isset($_POST["documentosRelacionar"]))) {
    $ajax = new AjaxFacturacion();
    $ajax->guiaRelacionar = $_POST["guiaRelacionar"];
    $ajax->documentoRelacionar = isset($_POST["documentoRelacionar"]) ? $_POST["documentoRelacionar"] : "";
    $ajax->documentosRelacionar = isset($_POST["documentosRelacionar"]) ? $_POST["documentosRelacionar"] : "";
    $ajax->ajaxRelacionarDocumentoGuia();
}
