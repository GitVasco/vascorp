<?php

session_start();

require_once '../controladores/articulos.controlador.php';
require_once '../modelos/articulos.modelo.php';
require_once '../controladores/clientes.controlador.php';
require_once '../modelos/clientes.modelo.php';

require_once '../controladores/pedidos.controlador.php';
require_once '../modelos/pedidos.modelo.php';

require_once '../controladores/movimientos.controlador.php';
require_once '../modelos/movimientos.modelo.php';

/**
 * Normaliza lectura del escáner para match con articulojf.articulo.
 * - Letra inicial (RL271013): código completo.
 * - Empieza con 0 (0057032): "1" + todos los dígitos → 10057032.
 * - Otros numéricos: todos los dígitos, sin truncar.
 */
function normalizarCodigoBarcodePedCv($scan)
{
    $limpio = trim(preg_replace('/[\r\n]+/', '', (string) $scan));
    if ($limpio === '') {
        return '';
    }
    if (preg_match('/^[a-zA-Z]/', $limpio)) {
        return $limpio;
    }
    preg_match_all('/\d/', $limpio, $matches);
    $soloDigitos = implode('', isset($matches[0]) ? $matches[0] : array());
    if ($soloDigitos === '') {
        return $limpio;
    }
    if ($soloDigitos[0] === '0') {
        return '1' . $soloDigitos;
    }
    return $soloDigitos;
}

/**
 * Hilos elásticos (marca ELASTICOS, modelo HIL*): precio en soles, no en lista USD (precio1).
 */
function esHiloElastico($modelo, $marca = null)
{
    $modelo = strtoupper(trim((string) $modelo));
    if (strpos($modelo, 'HIL') !== 0) {
        return false;
    }
    if ($marca !== null && strtoupper(trim((string) $marca)) !== 'ELASTICOS') {
        return false;
    }
    return true;
}

function listaPrecioHiloElastico($modelo, $lista, $marca = null)
{
    if ($lista === 'precio1' && esHiloElastico($modelo, $marca)) {
        return 'precio2';
    }
    return $lista;
}

class AjaxPedidos
{

    /* 
	* VISUALIZAR COLORES
	*/
    public function ajaxVerColores()
    {

        $valor = $this->modelo;

        $respuesta = controladorArticulos::ctrVerColores($valor);

        echo json_encode($respuesta);
    }

    /* 
	* VISUALIZAR COLORES
	*/
    public function ajaxVerColoresCantidades()
    {

        $pedido = $this->pedido;
        $modelo = $this->modeloA;

        $respuesta = controladorArticulos::ctrVerColoresCantidades($pedido, $modelo);

        echo json_encode($respuesta);
    }

    /* 
	* VISUALIZAR COLORES
	*/
    public function ajaxVerColoresCantidadesB()
    {

        $pedido = $this->pedidoT;
        $modelo = $this->modeloT;

        $respuesta = controladorArticulos::ctrVerColoresCantidadesB($pedido, $modelo);

        echo json_encode($respuesta);
    }

    /* 
	* VISUALIZAR COLORES
	*/
    public function ajaxVerDatos()
    {

        $modelo = $this->mod;
        $lista = listaPrecioHiloElastico($modelo, $this->modLista);

        $respuestaLista = controladorArticulos::ctrVerPrecios($modelo, $lista);

        echo json_encode($respuestaLista);
    }

    /* 
	* SACAR LA LISTA DE PRECIOS ASIGNADA
	*/
    public function ajaxVeLista()
    {

        $valor = $this->cliList;

        $respuestaDet = ControladorClientes::ctrVerLista($valor);

        echo json_encode($respuestaDet);
    }

    /* 
	* SACAR LA LISTA DE PRECIOS ASIGNADA
	*/
    public function ajaxBorrarModelo()
    {

        $modelo = $this->modelo;
        $pedido = $this->pedido;

        $respuesta = ModeloPedidos::mdlBorrarModelo($modelo, $pedido);

        echo json_encode($respuesta);
    }


    /* 
	* SACAR LA LISTA DE PRECIOS ASIGNADA
	*/
    public function ajaxBorrarArticulo()
    {

        $articulo = $this->articulo;
        $pedido = $this->pedido;

        $respuesta = ModeloPedidos::mdlBorrarArticulo($articulo, $pedido);

        echo json_encode($respuesta);
    }

    /* 
	* VER TALONARIO
	*/
    public function ajaxVerTalonario()
    {

        $serie = $this->serie;
        $talonario = $this->talonario;

        $respuesta = ModeloPedidos::mdlVerTalonario($serie, $talonario);

        echo json_encode($respuesta);
    }

    /* 
	* VER TALONARIO
	*/
    public function ajaxActualizarTalonario()
    {

        $serie = $this->serieA;
        $talonario = $this->talonarioA;

        $respuesta = ModeloPedidos::mdlSepararTalonario($serie, $talonario);

        echo json_encode($respuesta);
    }

    /* 
	* VER TALONARIO
	*/
    public function ajaxReiniciarTalonario()
    {

        $tipo = $this->tipo;

        $respuesta = ModeloPedidos::mdlReiniciarTalonario($tipo);

        echo json_encode($respuesta);
    }


    /* 
	* SACAR LA LISTA DE PRECIOS ASIGNADA
	*/
    public function ajaxDupicarPedido()
    {

        $codDup = $this->codDup;

        #vemos el numero que sigue en el talonario y actualizamos en +1
        $numero = ControladorMovimientos::ctrMostrarTalonario();
        $talonario = $numero["pedido"] + 1;



        $usuario = $_SESSION["id"];
        $talonarioN = $usuario . $talonario;

        //*COPIAR CABECERA
        $rptCab = ModeloPedidos::mdlDuplicarCabecera($codDup, $talonarioN);

        if ($rptCab == "ok") {

            //*COPIAR DETALLE
            $rptDet = ModeloPedidos::mdlDuplicarDetalle($codDup, $talonarioN);

            ModeloPedidos::mdlActualizarTalonario();
        }

        echo json_encode($rptDet);
    }

    //*TRAER CUENTAS
    public function ajaxTraerCuentas()
    {

        $valor = $this->documento;

        $respuesta = ControladorPedidos::ctrTraerCuentas($valor);

        echo json_encode($respuesta);
    }

    /* 
	* VER TALONARIO
	*/
    public function ajaxActualizarTotales()
    {

        $codPedido = $this->codPedido;

        $respuesta = ModeloPedidos::mdlActualizarTotales($codPedido);

        echo json_encode($respuesta);
    }

    /**
     * Totales para el formulario de guardado desde escaneo (sin líneas DOM como crear-pedidocv).
     */
    public function ajaxTotalesFormularioEscaneoCv()
    {

        $pedido = isset($_POST["pedidoTotalesCv"]) ? trim((string) $_POST["pedidoTotalesCv"]) : "";
        if ($pedido === "") {
            echo json_encode(array("ok" => false));
            return;
        }

        $cab = ControladorPedidos::ctrMostrarTemporal($pedido);
        if (!$cab || empty($cab["codigo"])) {
            echo json_encode(array("ok" => false));
            return;
        }

        $totalRow = ControladorPedidos::ctrMostrarTemporalTotal($pedido);
        $bruto = 0;
        if ($totalRow && isset($totalRow["totalArt"])) {
            $bruto = floatval($totalRow["totalArt"]);
        }

        $lista = isset($cab["lista"]) ? trim((string) $cab["lista"]) : "";
        $descN = 0;
        $subTotal = $bruto;
        $impNuevo = ($lista === "precio1") ? 0 : round($subTotal * 0.18, 2);
        $totalFin = ($lista === "precio1") ? round($subTotal, 2) : round($subTotal + $impNuevo, 2);

        echo json_encode(array(
            "ok" => true,
            "nuevoSubTotalA" => number_format($bruto, 2, ".", ""),
            "descTotal" => number_format($descN, 2, ".", ""),
            "subTotal" => number_format($subTotal, 2, ".", ""),
            "impTotal" => number_format($impNuevo, 2, ".", ""),
            "nuevoTotal" => number_format($totalFin, 2, ".", ""),
        ));
    }

    /**
     * Crea sólo la cabecera temporaljf (primer registro igual flujo AJAX de ítems),
     * para la pantalla de escaneo por código de barras.
     */
    public function ajaxCrearCabeceraTemporalEscaneoCv()
    {

        $cliente = isset($_POST["clienteEscaneoCab"]) ? trim((string) $_POST["clienteEscaneoCab"]) : "";
        $vendedor = isset($_POST["vendedorEscaneoCab"]) ? trim((string) $_POST["vendedorEscaneoCab"]) : "";
        $lista = isset($_POST["listaEscaneoCab"]) ? trim((string) $_POST["listaEscaneoCab"]) : "";
        $agencia = isset($_POST["agenciaEscaneoCab"]) ? trim((string) $_POST["agenciaEscaneoCab"]) : "";

        if (!isset($_SESSION["id"]) || $_SESSION["id"] === "" || $_SESSION["id"] === null) {
            echo json_encode(array("ok" => false, "mensaje" => "Sesión caducada. Volvé a iniciar sesión."));
            return;
        }

        if ($cliente === "" || $vendedor === "") {
            echo json_encode(array("ok" => false, "mensaje" => "Seleccioná cliente y vendedor."));
            return;
        }

        if ($lista === "" || !preg_match('/^precio[0-9]+$/', $lista)) {
            echo json_encode(array("ok" => false, "mensaje" => "Lista de precios no válida. Elegí cliente y revisá el vendedor."));
            return;
        }

        $numero = ControladorMovimientos::ctrMostrarTalonario();
        $talonario = $numero["pedido"] + 1;
        ModeloPedidos::mdlActualizarTalonario();

        $usuario = $_SESSION["id"];
        $talonarioN = $usuario . $talonario;

        $datos = array(
            "codigo"   => $talonarioN,
            "cliente"  => $cliente,
            "vendedor" => $vendedor,
            "lista"    => $lista,
            "agencia"  => $agencia,
        );

        $cab = ModeloPedidos::mdlGuardarTemporal("temporaljf", $datos);

        if ($cab == "ok") {
            echo json_encode(array("ok" => true, "pedido" => $talonarioN));
        } else {
            echo json_encode(array("ok" => false, "mensaje" => "No se pudo crear la cabecera del pedido."));
        }
    }

    /**
     * Incrementa +1 en detalle temporal por SKU (columna articulojf.articulo), lector código de barras — crear pedido CV.
     * No reutiliza el flujo modelo/modal; lista y pedido desde cabecera temporaljf.
     */
    public function ajaxIncrementarBarcodePedidoCv()
    {

        $articuloSkuRaw = isset($_POST["articuloBarcodeCv"]) ? trim((string) $_POST["articuloBarcodeCv"]) : "";
        $pedido = isset($_POST["pedidoBarcodeCv"]) ? trim((string) $_POST["pedidoBarcodeCv"]) : "";

        if ($articuloSkuRaw === "" || $pedido === "") {
            echo json_encode(array("ok" => false, "mensaje" => "Faltan pedido o código de artículo."));
            return;
        }

        $articuloSku = normalizarCodigoBarcodePedCv($articuloSkuRaw);

        $cabecera = ModeloPedidos::mdlMostrarTemporal("temporaljf", $pedido);
        if (!$cabecera || empty($cabecera["codigo"])) {
            echo json_encode(array("ok" => false, "mensaje" => "No se encontró la cabecera del pedido."));
            return;
        }

        $lista = isset($cabecera["lista"]) ? trim((string) $cabecera["lista"]) : "";
        if ($lista === "" || !preg_match('/^precio[0-9]+$/', $lista)) {
            echo json_encode(array("ok" => false, "mensaje" => "Lista de precios inválida en el pedido temporal."));
            return;
        }

        $filaArt = ModeloArticulos::mdlArticuloPorCodigo($articuloSku);
        if (!$filaArt || empty($filaArt["modelo"])) {
            echo json_encode(array(
                "ok" => false,
                "codigo" => $articuloSku,
                "mensaje" => "Código no encontrado: " . $articuloSku . ".",
            ));
            return;
        }

        $modelo = $filaArt["modelo"];
        $marca = isset($filaArt["marca"]) ? $filaArt["marca"] : null;
        $lista = listaPrecioHiloElastico($modelo, $lista, $marca);
        $precioLista = controladorArticulos::ctrVerPrecios($modelo, $lista);
        $precioBruto = isset($precioLista["precio"]) ? floatval($precioLista["precio"]) : 0;

        $advertencia = "";
        $precio = $precioBruto;
        if ($precio <= 0) {
            $precio = 0;
            $advertencia = "No hay precio en la lista seleccionada: la línea se grabará en S/ 0.00.";
        }

        $rpt = ModeloPedidos::mdlIncrementarArticuloTemporalPedidoCv($pedido, $articuloSku, $precio);

        if ($rpt === "ok") {
            echo json_encode(array(
                "ok" => true,
                "codigo" => $articuloSku,
                "advertencia" => $advertencia,
            ));
        } else {
            echo json_encode(array("ok" => false, "mensaje" => "No se pudo registrar la línea en el detalle."));
        }
    }

    /**
     * Listado JSON del detalle_temporal (+ descripción desde articulojf) para pantalla escaneo barcode.
     */
    public function ajaxListadoDetalleEscaneoCv()
    {

        $pedido = isset($_POST["pedidoListadoEscaneoCv"]) ? trim((string) $_POST["pedidoListadoEscaneoCv"]) : "";
        if ($pedido === "") {
            echo json_encode(array("ok" => false));
            return;
        }

        $cab = ControladorPedidos::ctrMostrarTemporal($pedido);
        if (!$cab || empty($cab["codigo"])) {
            echo json_encode(array("ok" => false, "mensaje" => "No se encontró la cabecera del pedido."));
            return;
        }

        $items = ControladorPedidos::ctrMostrarDetallesTemporalB($pedido);
        echo json_encode(array(
            "ok" => true,
            "items" => $items,
        ));
    }

    /**
     * Actualiza cantidad y precio de una línea (detalle_temporal), pantalla escaneo barcode.
     */
    public function ajaxActualizarLineaEscaneoCv()
    {

        $pedido = isset($_POST["pedidoEscaneoLinea"]) ? trim((string) $_POST["pedidoEscaneoLinea"]) : "";
        $articulo = isset($_POST["articuloEscaneoLinea"]) ? trim((string) $_POST["articuloEscaneoLinea"]) : "";
        $cantRaw = isset($_POST["cantidadEscaneoLinea"]) ? $_POST["cantidadEscaneoLinea"] : "";
        $precRaw = isset($_POST["precioEscaneoLinea"]) ? $_POST["precioEscaneoLinea"] : "";

        if ($pedido === "" || $articulo === "") {
            echo json_encode(array("ok" => false, "mensaje" => "Faltan datos del pedido o artículo."));
            return;
        }

        $cab = ControladorPedidos::ctrMostrarTemporal($pedido);
        if (!$cab || empty($cab["codigo"])) {
            echo json_encode(array("ok" => false, "mensaje" => "No existe la cabecera temporal."));
            return;
        }

        $cantidad = (int) $cantRaw;
        $precio = floatval(str_replace(",", ".", (string) $precRaw));

        $rpt = ModeloPedidos::mdlActualizarLineaDetalleTemporalEscaneo($pedido, $articulo, $cantidad, $precio);

        if ($rpt === "ok") {
            echo json_encode(array("ok" => true));
            return;
        }

        if ($rpt === "error_validacion") {
            echo json_encode(array(
                "ok" => false,
                "mensaje" => "Cantidad mínimo 1 y precio no negativo.",
            ));
            return;
        }

        echo json_encode(array("ok" => false, "mensaje" => "No se pudo actualizar la línea."));
    }

    /**
     * Elimina una línea (detalle_temporal), pantalla escaneo barcode.
     */
    public function ajaxEliminarLineaEscaneoCv()
    {

        $pedido = isset($_POST["pedidoEscaneoLineaEliminar"]) ? trim((string) $_POST["pedidoEscaneoLineaEliminar"]) : "";
        $articulo = isset($_POST["articuloEscaneoLineaEliminar"]) ? trim((string) $_POST["articuloEscaneoLineaEliminar"]) : "";

        if ($pedido === "" || $articulo === "") {
            echo json_encode(array("ok" => false, "mensaje" => "Faltan datos del pedido o artículo."));
            return;
        }

        $cab = ControladorPedidos::ctrMostrarTemporal($pedido);
        if (!$cab || empty($cab["codigo"])) {
            echo json_encode(array("ok" => false, "mensaje" => "No existe la cabecera temporal."));
            return;
        }

        $rpt = ModeloPedidos::mdlBorrarLineaDetalleTemporalEscaneo($pedido, $articulo);

        if ($rpt === "ok") {
            echo json_encode(array("ok" => true));
            return;
        }

        echo json_encode(array("ok" => false, "mensaje" => "No se pudo eliminar la línea."));
    }

    /* 
	* VER TALONARIO
	*/
    public function ajaxNuevoGuardarPedido()
    {

        $pedidoN = $this->pedidoN;
        $nuevoPedidoN = $this->nuevoPedidoN;
        $clienteN = $this->clienteN;
        $vendedorN = $this->vendedorN;
        $listaN = $this->listaN;
        $agenciaN = $this->agenciaN;
        $modeloN = $this->modeloN;
        $precioN = $this->precioN;
        $articulosN = $this->articulosN;

        $articulosN = json_decode($_POST["articulosN"], true);

        if (count($articulosN) > 0) {

            if ($pedidoN === "") {

                $numero = ControladorMovimientos::ctrMostrarTalonario();
                $talonario = $numero["pedido"] + 1;
                ModeloPedidos::mdlActualizarTalonario();

                $usuario = $_SESSION["id"];
                $talonarioN = $usuario . $talonario;

                $datos = array(
                    "codigo"    => $talonarioN,
                    "cliente"   => $clienteN,
                    "vendedor"  => $vendedorN,
                    "lista"     => $listaN,
                    "usuario"   => $usuario,
                    "agencia"   => $agenciaN
                );

                $cab = ModeloPedidos::mdlGuardarTemporal("temporaljf", $datos);

                foreach ($articulosN as $key => $value) {
                    if ($value["value"] > 0) {
                        $datosDetalle = array(
                            "codigo"    => $talonarioN,
                            "articulo"  => $value["name"],
                            "cantidad"  => $value["value"],
                            "precio"    => $precioN
                        );
                        $respuesta = ModeloPedidos::mdlGuardarTemporalDetalle("detalle_temporal", $datosDetalle);
                    }
                }

                if ($cab == "ok") {
                    echo json_encode($talonarioN);
                }
            } else {
                $limpiar = ModeloPedidos::mdlEliminarDetalleTemporalB("detalle_temporal", $pedidoN, $modeloN);

                foreach ($articulosN as $key => $value) {
                    if ($value["value"] > 0) {
                        $datosDetalle = array(
                            "codigo"    => $pedidoN,
                            "articulo"  => $value["name"],
                            "cantidad"  => $value["value"],
                            "precio"    => $precioN
                        );
                        $respuesta = ModeloPedidos::mdlGuardarTemporalDetalle("detalle_temporal", $datosDetalle);
                    }
                }

                echo json_encode("toast");
            }
        }
    }
}

/* 
 * VISUALIZAR COLORES
*/
if (isset($_POST["modelo"])) {

    $visualizarMateriaPrimaDetalle = new AjaxPedidos();
    $visualizarMateriaPrimaDetalle->modelo = $_POST["modelo"];
    $visualizarMateriaPrimaDetalle->ajaxVerColores();
}

/* 
 * VISUALIZAR COLORES Y MODIFICAR
*/
if (isset($_POST["pedido"])) {

    $verColoresyCantidades = new AjaxPedidos();
    $verColoresyCantidades->pedido = $_POST["pedido"];
    $verColoresyCantidades->modeloA = $_POST["modeloA"];
    $verColoresyCantidades->ajaxVerColoresCantidades();
}

/* 
 * VISUALIZAR COLORES Y MODIFICAR
*/
if (isset($_POST["pedidoT"])) {

    $verColoresyCantidades = new AjaxPedidos();
    $verColoresyCantidades->pedidoT = $_POST["pedidoT"];
    $verColoresyCantidades->modeloT = $_POST["modeloT"];
    $verColoresyCantidades->ajaxVerColoresCantidadesB();
}

/* 
 * VISUALIZAR precios y otros
*/
if (isset($_POST["mod"])) {

    $visualizarPrecios = new AjaxPedidos();
    $visualizarPrecios->mod = $_POST["mod"];
    $visualizarPrecios->modLista = $_POST["modLista"];
    $visualizarPrecios->ajaxVerDatos();
}

/* 
 * SACAR LA LISTA DE PRECIOS ASIGNADA
*/
if (isset($_POST["cliList"])) {

    $visualizarListaPrecios = new AjaxPedidos();
    $visualizarListaPrecios->cliList = $_POST["cliList"];
    $visualizarListaPrecios->ajaxVeLista();
}

/* 
 * PARA BORRAR POR MODELO
*/
if (isset($_POST["modeloB"])) {

    $borrarModelo = new AjaxPedidos();
    $borrarModelo->modelo = $_POST["modeloB"];
    $borrarModelo->pedido = $_POST["pedidoB"];
    $borrarModelo->ajaxBorrarModelo();
}

if (isset($_POST["articuloC"])) {

    $borrarModelo = new AjaxPedidos();
    $borrarModelo->articulo = $_POST["articuloC"];
    $borrarModelo->pedido = $_POST["pedidoC"];
    $borrarModelo->ajaxBorrarArticulo();
}

/* 
 * PARA BORRAR POR MODELO
*/
if (isset($_POST["codDup"])) {

    $borrarModelo = new AjaxPedidos();
    $borrarModelo->codDup = $_POST["codDup"];
    $borrarModelo->ajaxDupicarPedido();
}

/* 
 * VER TALONARIOS QUE TRAE
*/
if (isset($_POST["talonario"])) {

    $verColoresyCantidades = new AjaxPedidos();
    $verColoresyCantidades->serie = $_POST["serie"];
    $verColoresyCantidades->talonario = $_POST["talonario"];
    $verColoresyCantidades->ajaxVerTalonario();
}

/* 
 * ACTUALIZAR Y SEPARAR EL COONTROL
*/
if (isset($_POST["talonarioA"])) {

    $verColoresyCantidades = new AjaxPedidos();
    $verColoresyCantidades->serieA = $_POST["serieA"];
    $verColoresyCantidades->talonarioA = $_POST["talonarioA"];
    $verColoresyCantidades->ajaxActualizarTalonario();
}


/* 
 * REINICIAR TALONARIO
*/
if (isset($_POST["tipo"])) {

    $verColoresyCantidades = new AjaxPedidos();
    $verColoresyCantidades->tipo = $_POST["tipo"];
    $verColoresyCantidades->ajaxReiniciarTalonario();
}


/* 
 * Treaemos las cuentas
*/
if (isset($_POST["documento"])) {

    $verColoresyCantidades = new AjaxPedidos();
    $verColoresyCantidades->documento = $_POST["documento"];
    $verColoresyCantidades->ajaxTraerCuentas();
}

/* 
 * Actualizar totales
*/
if (isset($_POST["codPedido"])) {

    $verColoresyCantidades = new AjaxPedidos();
    $verColoresyCantidades->codPedido = $_POST["codPedido"];
    $verColoresyCantidades->ajaxActualizarTotales();
}


/* 
 * Totales formulario escaneo (JSON).
*/
if (isset($_POST["ajaxTotalesFormularioEscaneoCv"])) {

    $t = new AjaxPedidos();
    $t->ajaxTotalesFormularioEscaneoCv();

}

/* 
 * Cabecera temporal — pantalla escaneo código de barras (sin detalle inicial).
*/
if (isset($_POST["crearCabeceraEscaneoCv"])) {

    $crearCabEscaneo = new AjaxPedidos();
    $crearCabEscaneo->ajaxCrearCabeceraTemporalEscaneoCv();

}

/* 
 * Lector código de barras — crear pedido CV (+1 cantidad por lectura).
*/
if (isset($_POST["barcodePedidoCvAccion"])) {

    $incrementarBarcodePedidoCv = new AjaxPedidos();
    $incrementarBarcodePedidoCv->ajaxIncrementarBarcodePedidoCv();

}

/* 
 * Listado detalle temporal — pantalla escaneo barcode (JSON).
*/
if (isset($_POST["ajaxListadoDetalleEscaneoCv"])) {

    $listEscaneoCv = new AjaxPedidos();
    $listEscaneoCv->ajaxListadoDetalleEscaneoCv();

}

/* 
 * Editar línea detalle temporal — pantalla escaneo barcode.
*/
if (isset($_POST["ajaxActualizarLineaEscaneoCv"])) {

    $linEscaneoCv = new AjaxPedidos();
    $linEscaneoCv->ajaxActualizarLineaEscaneoCv();

}

/* 
 * Eliminar línea detalle temporal — pantalla escaneo barcode.
*/
if (isset($_POST["ajaxEliminarLineaEscaneoCv"])) {

    $delLinEscaneoCv = new AjaxPedidos();
    $delLinEscaneoCv->ajaxEliminarLineaEscaneoCv();

}

/* 
 * Guardar Modelo Nuevo
*/
if (isset($_POST["pedidoN"])) {

    $activar = new AjaxPedidos();
    $activar->pedidoN = $_POST["pedidoN"];
    $activar->nuevoPedidoN = $_POST["nuevoPedidoN"];
    $activar->clienteN = $_POST["clienteN"];
    $activar->vendedorN = $_POST["vendedorN"];
    $activar->listaN = $_POST["listaN"];
    $activar->agenciaN = $_POST["agenciaN"];
    $activar->modeloN = $_POST["modeloN"];
    $activar->precioN = $_POST["precioN"];
    $activar->articulosN = $_POST["articulosN"];
    $activar->ajaxNuevoGuardarPedido();
}
