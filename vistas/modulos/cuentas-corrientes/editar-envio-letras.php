<?php
if (!isset($_GET["id"]) || (int) $_GET["id"] <= 0) {
    echo '<script>window.location="ver-envio-letras";</script>';
    return;
}

$idEnvio = (int) $_GET["id"];
$cabecera = ControladorCuentas::ctrDetalleEnvioLetra($idEnvio);
if (empty($cabecera["ok"]) || empty($cabecera["cabecera"])) {
    echo '<script>
        swal({
            type: "error",
            title: "No se encontró el envío",
            showConfirmButton: true,
            confirmButtonText: "Cerrar"
        }).then(function(result){
            if(result.value){ window.location="ver-envio-letras"; }
        });
    </script>';
    return;
}

$envio = $cabecera["cabecera"];
$detalle = isset($cabecera["detalle"]) ? $cabecera["detalle"] : array();
require_once "controladores/envio-letras-excel.lib.php";
$formatoActual = EnvioLetrasExcelLib::formatoDesdeArchivo(isset($envio["archivo"]) ? $envio["archivo"] : "");

if (!function_exists("envioLetraFechaVenAttr")) {
    function envioLetraFechaVenAttr($fechaVen)
    {
        $fechaVen = trim((string) $fechaVen);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $fechaVen, $m)) {
            return $m[3] . $m[2] . substr($m[1], 2);
        }
        return preg_replace('/\D+/', '', $fechaVen);
    }
}
?>
<div class="content-wrapper">

    <section class="content-header">
        <h1>Editar envío de letras</h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li><a href="ver-envio-letras">Envíos de letras</a></li>
            <li class="active">Editar</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-lg-5 col-xs-12">
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title">Código <?php echo htmlspecialchars($envio["codigo"]); ?></h3>
                    </div>

                    <form role="form" method="post" class="formularioEnvioLetra">
                        <div class="box-body">
                            <div class="box">

                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-user"></i></span>
                                        <input type="text" class="form-control" id="usuario" name="usuario"
                                            value="<?php echo htmlspecialchars($_SESSION["nombre"]); ?>" readonly>
                                        <input type="hidden" name="idUsuario" value="<?php echo (int) $_SESSION["id"]; ?>">
                                        <input type="hidden" name="idEnvioLetra" value="<?php echo (int) $envio["id"]; ?>">
                                        <input type="hidden" id="codigoEnvioEdicion" value="<?php echo htmlspecialchars($envio["codigo"]); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-key"></i></span>
                                        <input type="text" class="form-control" id="editarCodigoEnvio" name="editarCodigoEnvio"
                                            value="<?php echo htmlspecialchars($envio["codigo"]); ?>" readonly>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Formato para el banco</label>
                                    <select class="form-control" name="formatoEnvioLetra" id="formatoEnvioLetra">
                                        <option value="txt" <?php echo $formatoActual === "txt" ? "selected" : ""; ?>>TXT — formato vigente (recomendado)</option>
                                        <option value="excel" <?php echo $formatoActual === "excel" ? "selected" : ""; ?>>Excel nuevo — con dirección (pendiente confirmación banco)</option>
                                    </select>
                                    <p class="help-block" style="margin-bottom:0;">
                                        Mientras el banco no confirme el Excel, use <b>TXT</b>.
                                    </p>
                                </div>

                                <div class="box box-primary">
                                    <div class="row">
                                        <div class="col-xs-3"><label>Num. cta</label></div>
                                        <div class="col-xs-7"><label>Cliente</label></div>
                                        <div class="col-xs-2"><label>Monto</label></div>
                                    </div>
                                </div>

                                <div class="form-group row nuevoCampoEnvio">
                                    <?php
                                    foreach ($detalle as $fila) {
                                        if (empty($fila["id_cuenta"])) {
                                            continue;
                                        }
                                        $idcuenta = (int) $fila["id_cuenta"];
                                        $numcta = !empty($fila["num_cta_envio"])
                                            ? $fila["num_cta_envio"]
                                            : str_replace("-", "", (string) $fila["num_cta"]);
                                        $fechaVen = envioLetraFechaVenAttr(isset($fila["fecha_ven"]) ? $fila["fecha_ven"] : "");
                                        $nombres = isset($fila["nombres"]) ? $fila["nombres"] : "";
                                        $apePat = isset($fila["ape_paterno"]) ? $fila["ape_paterno"] : "";
                                        $apeMat = isset($fila["ape_materno"]) ? $fila["ape_materno"] : "";
                                        $documento = isset($fila["documento"]) ? $fila["documento"] : "";
                                        $clienteNom = isset($fila["cliente_nombre"]) ? $fila["cliente_nombre"] : trim($nombres . " " . $apePat . " " . $apeMat);
                                        $codCli = isset($fila["cod_cliente"]) ? $fila["cod_cliente"] : "";
                                        $clienteLabel = trim($codCli . "-" . $clienteNom, "-");
                                        $monto = isset($fila["saldo"]) ? $fila["saldo"] : 0;
                                        ?>
                                        <div class="row" style="padding:5px 15px">
                                            <div class="col-xs-3" style="padding-right:0px">
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <button type="button" class="btn btn-danger btn-xs quitarEnvioCuenta" idcuenta="<?php echo $idcuenta; ?>">
                                                            <i class="fa fa-times"></i>
                                                        </button>
                                                    </span>
                                                    <input type="text" class="form-control nuevoNumCuenta" name="nrocta"
                                                        value="<?php echo htmlspecialchars($numcta); ?>"
                                                        idcuenta="<?php echo $idcuenta; ?>"
                                                        fecha_ven="<?php echo htmlspecialchars($fechaVen); ?>"
                                                        readonly required>
                                                </div>
                                            </div>
                                            <div class="col-xs-7" style="padding-right:0px">
                                                <input type="text" class="form-control nuevaDescripcionCliente" name="cliente"
                                                    value="<?php echo htmlspecialchars($clienteLabel); ?>"
                                                    nombres="<?php echo htmlspecialchars($nombres); ?>"
                                                    ape_pat="<?php echo htmlspecialchars($apePat); ?>"
                                                    ape_mat="<?php echo htmlspecialchars($apeMat); ?>"
                                                    documento="<?php echo htmlspecialchars($documento); ?>"
                                                    readonly required>
                                            </div>
                                            <div class="col-xs-2">
                                                <input type="number" class="form-control nuevoMonto" name="nuevoMonto"
                                                    value="<?php echo htmlspecialchars($monto); ?>" readonly required>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>

                                <input type="hidden" id="listaEnvioLetra" name="listaEnvioLetra">

                                <div class="row">
                                    <div class="col-xs-6 pull-right">
                                        <table class="table">
                                            <thead>
                                                <tr><th>Cantidad de letras</th></tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td style="width: 50%">
                                                        <div class="input-group">
                                                            <span class="input-group-addon"><i class="fa fa-paper-plane-o"></i></span>
                                                            <input type="text" min="1" class="form-control input-lg"
                                                                id="nuevoTotalCuentaEnvio" name="nuevoTotalCuentaEnvio"
                                                                total="<?php echo count($detalle); ?>"
                                                                value="<?php echo count($detalle); ?>"
                                                                placeholder="0" readonly required>
                                                            <input type="hidden" name="totalEnvioCuentas" id="totalEnvioCuentas"
                                                                value="<?php echo count($detalle); ?>">
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <hr>
                                <br>
                            </div>
                        </div>

                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary pull-right">
                                <i class="fa fa-floppy-o"></i> Guardar cambios
                            </button>
                            <a href="ver-envio-letras" class="btn btn-danger">
                                <i class="fa fa-times-circle"></i> Cancelar
                            </a>
                        </div>
                    </form>

                    <?php
                    $editarEnvioLetras = new ControladorCuentas();
                    $editarEnvioLetras->ctrEditarEnvioLetras();
                    ?>
                </div>
            </div>

            <div class="col-lg-7 col-xs-12">
                <div class="box box-warning">
                    <div class="box-body">
                        <table class="table table-bordered table-striped dt-responsive tablaEnvioLetras" width="100%">
                            <thead>
                                <tr>
                                    <th style="width:10px">Tipo Doc.</th>
                                    <th>Nro. cuenta</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th style="width:20px">Vencimiento</th>
                                    <th>Monto</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
window.document.title = "Editar Envío Letras";
$(function () {
    if (typeof listarCuentas === "function") {
        listarCuentas();
    }
    if (typeof sumarTotalEnvio === "function") {
        sumarTotalEnvio();
    }
    if (typeof quitarAgregarEnvioCuenta === "function") {
        quitarAgregarEnvioCuenta();
    }
});
</script>
