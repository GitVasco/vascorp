<?php
session_start();

require_once "../controladores/cuentas.controlador.php";
require_once "../modelos/cuentas.modelo.php";


class AjaxCuentas
{
    /*=============================================
      EDITAR CUENTA
      =============================================*/

    public $idCuenta;

    public function ajaxEditarCuenta()
    {
        $item = "id";
        $valor = $this->idCuenta;

        $respuesta = ControladorCuentas::ctrMostrarCuentas($item, $valor);

        echo json_encode($respuesta);
    }

    public $idCancelacion;

    public function ajaxEditarCancelacion()
    {
        $item = "id";
        $valor = $this->idCancelacion;

        $respuesta = ModeloCuentas::mdlMostrarCancelacion("cuenta_ctejf", $item, $valor);

        echo json_encode($respuesta);
    }

    public $numCta;
    public $codCta;
    public function ajaxCancelarCuenta()
    {

        $numCta = $this->numCta;
        $codCta = $this->codCta;


        $respuesta = ControladorCuentas::ctrMostrarCuentasV2($numCta, $codCta);

        echo json_encode($respuesta);
    }

    public $clienteCredito;

    public function ajaxCuentaCredito()
    {
        $valor = $this->clienteCredito;

        $respuesta = ControladorCuentas::ctrMostrarCuentaCredito($valor);

        echo json_encode($respuesta);
    }

    public $clienteDeuda;

    public function ajaxCuentaDeuda()
    {
        $valor = $this->clienteDeuda;

        $respuesta = ControladorCuentas::ctrMostrarCuentaDeuda($valor);

        echo json_encode($respuesta);
    }

    public $clienteDeudaVencida;

    public function ajaxCuentaDeudaVencida()
    {
        $valor = $this->clienteDeudaVencida;

        $respuesta = ControladorCuentas::ctrMostrarCuentaDeudaVencida($valor);

        echo json_encode($respuesta);
    }

    public $letraCuenta;

    public function ajaxCuentaLetras()
    {
        $valor = $this->letraCuenta;

        $respuesta = ControladorCuentas::ctrMostrarCuentasUnicos("id", $valor);

        echo json_encode($respuesta);
    }

    public $datosCuenta;
    public function ajaxCrearCuentaNota()
    {
        $valor = $this->datosCuenta;
        $datos = json_decode($valor);

        $usureg = $_SESSION["nombre"];
        $pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);

        foreach ($datos->{"datosCuenta"} as  $value) {
            $doc = $value->{"tipo_doc"};
            $cta = $value->{"num_cta"};
            $cli = $value->{"cliente"};
            $vend = $value->{"vendedor"};
            $monto = $value->{"monto"};
            $saldo = $value->{"saldo"};
            $fecha = $value->{"fecha"};
            $estado = $value->{"estado"};
            $nota = $value->{"notas"};
            $reno = $value->{"renovacion"};
            $prot = $value->{"protesta"};
            $mon = $value->{"tip_mon"};
            $pago = $value->{"cod_pago"};
            $origen = $value->{"doc_origen"};
            $mov = $value->{"tip_mov"};
            $user = $value->{"usuario"};
            $arregloCuenta = array(
                "tipo_doc" => $doc,
                "num_cta" => $cta,
                "cliente" => $cli,
                "vendedor" => $vend,
                "fecha" => $fecha,
                "fecha_ven" => $fecha,
                "tip_mon" => $mon,
                "monto" => $monto,
                "estado" => $estado,
                "notas" => $nota,
                "cod_pago" => $pago,
                "doc_origen" => $origen,
                "renovacion" => $reno,
                "protesta" => $prot,
                "usuario" => $user,
                "saldo" => $saldo,
                "tip_mov" => $mov,
                "usureg" => $usureg,
                "pcreg" => $pcreg
            );

            $respuesta = ModeloCuentas::mdlIngresarCuenta("cuenta_ctejf", $arregloCuenta);
        }

        echo $respuesta;
    }

    /*=============================================
      VALIDAR DOCUMENTO DE CUENTA
      =============================================*/
    public $documento;
    public function ajaxValidarDocumento()
    {
        $item = "num_cta";
        $valor = $this->documento;
        $item2 = "tipo_doc";
        $valor2 = "08";
        $respuesta = ControladorCuentas::ctrValidarCuenta($item, $valor, $item2, $valor2);
        echo json_encode($respuesta);
    }

    /*=============================================
      EDITAR DOCUMENTO DE CUENTA
      =============================================*/
    public $datosCuenta2;
    public function ajaxEditarCuentaNota()
    {
        $valor = $this->datosCuenta2;
        $datos = json_decode($valor);
        foreach ($datos->{"datosCuenta"} as  $value) {
            $id = $value->{"id"};
            $doc = $value->{"tipo_doc"};
            $cta = $value->{"num_cta"};
            $cli = $value->{"cliente"};
            $vend = $value->{"vendedor"};
            $monto = $value->{"monto"};
            $saldo = $value->{"saldo"};
            $fecha = $value->{"fecha"};
            $estado = $value->{"estado"};
            $nota = $value->{"notas"};
            $reno = $value->{"renovacion"};
            $prot = $value->{"protesta"};
            $mon = $value->{"tip_mon"};
            $pago = $value->{"cod_pago"};
            $origen = $value->{"doc_origen"};
            $user = $value->{"usuario"};
            $arregloCuenta = array(
                "id" => $id,
                "tipo_doc" => $doc,
                "num_cta" => $cta,
                "cliente" => $cli,
                "vendedor" => $vend,
                "fecha" => $fecha,
                "tip_mon" => $mon,
                "monto" => $monto,
                "estado" => $estado,
                "notas" => $nota,
                "cod_pago" => $pago,
                "doc_origen" => $origen,
                "renovacion" => $reno,
                "protesta" => $prot,
                "usuario" => $user,
                "saldo" => $saldo
            );

            $respuesta = ModeloCuentas::mdlEditarCuenta("cuenta_ctejf", $arregloCuenta);
        }


        echo $respuesta;
    }

    public $documentoMotivo;

    public function ajaxMostrarMotivos()
    {
        $item = "tipo_dato";
        $valor = $this->documentoMotivo;

        $respuesta = ControladorCuentas::ctrMostrarPagos($item, $valor);

        echo json_encode($respuesta);
    }

    public $documentoSalida;

    public function ajaxNombreDocumento()
    {
        $item = "codigo";
        $valor = $this->documentoSalida;

        $respuesta = ControladorCuentas::ctrMostrarPagos($item, $valor);

        echo json_encode($respuesta);
    }

    public $clientePagos;
    public function ajaxUltPagos()
    {

        $cliente = $this->clientePagos;

        $respuesta = ControladorCuentas::ctrUltPagos($cliente);

        echo json_encode($respuesta);
    }

    public $idCta;
    public $tipo_doc;
    public $num_cta;
    public $cliente;
    public $vendedor;
    public $monto;
    public $saldo;
    public $fecha;
    public $fecha_ven;
    public $doc_origen;
    public $codigoCancelacion;
    public $fechaCancelacion;
    public $montoCancelacion;
    public $notaCancelacion;

    public function ajaxCancelarDocumento()
    {
        $idCta = $this->idCta;
        $tipo_doc = $this->tipo_doc;
        $num_cta = $this->num_cta;
        $cliente = $this->cliente;
        $vendedor = $this->vendedor;
        $monto = $this->monto;
        $saldo = $this->saldo;
        $fecha = $this->fecha;
        $fecha_ven = $this->fecha_ven;
        $doc_origen = $this->doc_origen;
        $codigoCancelacion = $this->codigoCancelacion;
        $fechaCancelacion = $this->fechaCancelacion;
        $montoCancelacion = $this->montoCancelacion;
        $notaCancelacion = $this->notaCancelacion;

        $tabla = "cuenta_ctejf";
        $usureg = $_SESSION["nombre"];
        $pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);

        $datos = [
            "id"            => $idCta,
            "tipo_doc"      => $tipo_doc,
            "num_cta"       => $num_cta,
            "cliente"       => $cliente,
            "vendedor"      => $vendedor,
            "monto"         => $montoCancelacion,
            "notas"         => $notaCancelacion,
            "usuario"       => $_SESSION["id"],
            "fecha"         => $fechaCancelacion,
            "fecha_ven"     => $fecha_ven,
            "cod_pago"      => $codigoCancelacion,
            "doc_origen"    => $num_cta,
            "saldo"         => 0,
            "tip_mov"       => "-",
            "usureg"        => $usureg,
            "pcreg"         => $pcreg,
            "fecha_ori"     => $fecha
        ];

        $saldoNuevo = $saldo - $montoCancelacion;
        $estado = $saldoNuevo >= -0.5 && $saldoNuevo <= 0.5 ? "CANCELADO" : "PENDIENTE";
        $ultPago = $fechaCancelacion;
        $id = $idCta;

        $actualizar = ModeloCuentas::mdlActualizarCuenta($saldoNuevo, $estado, $ultPago, $id);
        if ($actualizar == "ok") {
            $respuesta = ModeloCuentas::mdlIngresarCuenta($tabla, $datos);
        } else {
            $respuesta = "error";
        }

        echo json_encode($respuesta);
    }

    public $idsCredipagos;
    public function ajaxEliminarCredipagos()
    {
        $ids = $this->idsCredipagos;
        $ids = json_decode($ids, true);

        // configuramos la zona horaria
        date_default_timezone_set('America/Asuncion');

        $errores = 0;

        foreach ($ids as $id) {
            $cancelaciones = ModeloCuentas::mdlMostrarCancelacion("cuenta_ctejf", "id", $id);

            $origen = ControladorCuentas::ctrMostrarCuentasV2($cancelaciones["num_cta"], $cancelaciones["tipo_doc"]);

            $idOrigen = $origen["id"];

            $saldoNuevo = $origen["saldo"] + $cancelaciones["monto"];

            $saldoNuevo >= 0 ? ModeloCuentas::mdlActualizarEstado($idOrigen) : "";

            ModeloCuentas::mdlActualizarUnDato(
                "cuenta_ctejf",
                "saldo",
                $saldoNuevo,
                $idOrigen
            );

            $datosBackup = [
                "id" => $cancelaciones["id"],
                "usuario_bkp" => $_SESSION["nombre"],
                "fecha_bkp" => date("Y-m-d H:i:s"),
                "pc_bkp" => gethostbyaddr($_SERVER['REMOTE_ADDR'])
            ];
            ModeloCuentas::mdlIngresarCuentaBckp2("cuenta_cte_bkpjf", $datosBackup);

            $respuesta = ModeloCuentas::mdlEliminarCuenta("cuenta_ctejf", $id);

            if (!$respuesta) {
                $errores++;
            }
        }

        if ($errores === 0) {
            echo json_encode(["status" => 200, "message" => "Todos los IDs fueron procesados correctamente"]);
        } else {
            echo json_encode(["status" => 400, "message" => "Hubo errores en el procesamiento", "errores" => $errores]);
        }
    }
}


/*=============================================
    EDITAR CUENTA
    =============================================*/
if (isset($_POST["idCuenta"])) {

    $tipoPago = new AjaxCuentas();
    $tipoPago->idCuenta = $_POST["idCuenta"];
    $tipoPago->ajaxEditarCuenta();
}
/*=============================================
    EDITAR CANCELACION
    =============================================*/
if (isset($_POST["idCancelacion"])) {

    $tipoCancelacion = new AjaxCuentas();
    $tipoCancelacion->idCancelacion = $_POST["idCancelacion"];
    $tipoCancelacion->ajaxEditarCancelacion();
}

/*=============================================
    CANCELAR CUENTA
    =============================================*/
if (isset($_POST["numCta"])) {

    $cancelaCuenta = new AjaxCuentas();
    $cancelaCuenta->numCta = $_POST["numCta"];
    $cancelaCuenta->codCta = $_POST["codCta"];
    $cancelaCuenta->ajaxCancelarCuenta();
}

/*=============================================
    MOSTRAR CREDITO
    =============================================*/
if (isset($_POST["clienteCredito"])) {

    $cuentaCredito = new AjaxCuentas();
    $cuentaCredito->clienteCredito = $_POST["clienteCredito"];
    $cuentaCredito->ajaxCuentaCredito();
}

/*=============================================
    MOSTRAR DEUDA
    =============================================*/
if (isset($_POST["clienteDeuda"])) {

    $cuentaDeuda = new AjaxCuentas();
    $cuentaDeuda->clienteDeuda = $_POST["clienteDeuda"];
    $cuentaDeuda->ajaxCuentaDeuda();
}

/*=============================================
    MOSTRAR DEUDA
    =============================================*/
if (isset($_POST["clienteDeudaVencida"])) {

    $cuentaDeudaVencida = new AjaxCuentas();
    $cuentaDeudaVencida->clienteDeudaVencida = $_POST["clienteDeudaVencida"];
    $cuentaDeudaVencida->ajaxCuentaDeudaVencida();
}

/*=============================================
    ENVIO LETRAS
    =============================================*/
if (isset($_POST["letraCuenta"])) {

    $cuentaLetras = new AjaxCuentas();
    $cuentaLetras->letraCuenta = $_POST["letraCuenta"];
    $cuentaLetras->ajaxCuentaLetras();
}

/*=============================================
    CREAR CUENTA
    =============================================*/
if (isset($_POST["jsonCuenta"])) {

    $crearCuenta = new AjaxCuentas();
    $crearCuenta->datosCuenta = $_POST["jsonCuenta"];
    $crearCuenta->ajaxCrearCuentaNota();
}

/*=============================================
    VALIDAR CUENTA
    =============================================*/
if (isset($_POST["documento"])) {
    $validarDocumento = new AjaxCuentas();
    $validarDocumento->documento = $_POST["documento"];
    $validarDocumento->ajaxValidarDocumento();
}

/*=============================================
    EDITAR CUENTA
    =============================================*/
if (isset($_POST["jsonCuenta2"])) {

    $editarCuenta = new AjaxCuentas();
    $editarCuenta->datosCuenta2 = $_POST["jsonCuenta2"];
    $editarCuenta->ajaxEditarCuentaNota();
}

/*=============================================
    MOSTRAR MOTIVO CREDITO
    =============================================*/
if (isset($_POST["documentoMotivo"])) {

    $motivos = new AjaxCuentas();
    $motivos->documentoMotivo = $_POST["documentoMotivo"];
    $motivos->ajaxMostrarMotivos();
}


/*=============================================
    MOSTRAR DESCRIPCION MAESTRA
    =============================================*/
if (isset($_POST["documentoSalida"])) {
    $descripcionDocumento = new AjaxCuentas();
    $descripcionDocumento->documentoSalida = $_POST["documentoSalida"];
    $descripcionDocumento->ajaxNombreDocumento();
}


/*=============================================
    MOSTRAR PAGOS DE LOS ULTIMOS 6 MESES
    =============================================*/
if (isset($_POST["clientePagos"])) {
    $ult_pagos = new AjaxCuentas();
    $ult_pagos->clientePagos = $_POST["clientePagos"];
    $ult_pagos->ajaxUltPagos();
}


/***************************************
 * Cancelar documento con ajax
 ***************************************/
if (isset($_POST["idCta"])) {
    $cancelaCuenta = new AjaxCuentas();
    $cancelaCuenta->idCta = $_POST["idCta"];
    $cancelaCuenta->tipo_doc = $_POST["tipo_doc"];
    $cancelaCuenta->num_cta = $_POST["num_cta"];
    $cancelaCuenta->cliente = $_POST["cliente"];
    $cancelaCuenta->vendedor = $_POST["vendedor"];
    $cancelaCuenta->monto = $_POST["monto"];
    $cancelaCuenta->saldo = $_POST["saldo"];
    $cancelaCuenta->fecha = $_POST["fecha"];
    $cancelaCuenta->fecha_ven = $_POST["fecha_ven"];
    $cancelaCuenta->doc_origen = $_POST["doc_origen"];
    $cancelaCuenta->codigoCancelacion = $_POST["codigoCancelacion"];
    $cancelaCuenta->fechaCancelacion = $_POST["fechaCancelacion"];
    $cancelaCuenta->montoCancelacion = $_POST["montoCancelacion"];
    $cancelaCuenta->notaCancelacion = $_POST["notaCancelacion"];
    $cancelaCuenta->ajaxCancelarDocumento();
}

if (isset($_POST["idsCredipagos"])) {
    $eliminarCredipagos = new AjaxCuentas();
    $eliminarCredipagos->idsCredipagos = $_POST["idsCredipagos"];
    $eliminarCredipagos->ajaxEliminarCredipagos();
}
