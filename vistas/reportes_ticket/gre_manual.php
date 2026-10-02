<?php
session_start();

date_default_timezone_set("America/Lima");

require_once "../../modelos/conexion.php";
require_once "../../modelos/gre-manual.modelo.php";
require_once "../../controladores/gre-manual.controlador.php";

if (!ControladorGreManual::puedeUsar()) {
    http_response_code(403);
    exit("Sin permiso.");
}

$g = ModeloGreManual::mdlObtener(isset($_GET["id"]) ? (int) $_GET["id"] : 0);
if (!$g) {
    http_response_code(404);
    exit("Guía no encontrada.");
}

$r = ControladorGreManual::remitente();
$docs = $g["docs_rel"] ? json_decode($g["docs_rel"], true) : array();
$privado = $g["modalidad"] === "02";
$interna = $g["tipo"] === "INTERNA";
$tipoDoc = array("0" => "S/RUC", "1" => "DNI", "4" => "C.E.", "6" => "RUC", "7" => "PASAPORTE", "A" => "CED. DIPL.");
$tipoRel = array("01" => "DAM", "02" => "Orden de entrega", "03" => "SCOP", "04" => "Manifiesto de carga", "05" => "Const. detracción", "06" => "Otros");

function h($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
}
function fecha($f)
{
    return $f ? date("d/m/Y", strtotime($f)) : "";
}
function num($v)
{
    $v = rtrim(rtrim(number_format((float) $v, 3, ".", ""), "0"), ".");
    return $v === "" ? "0" : $v;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Guía <?= h($g["documento"]); ?></title>
    <style>
        @page { size: A4; margin: 12mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #000; margin: 0; }
        .hoja { max-width: 190mm; margin: 0 auto; position: relative; }
        .cab { display: flex; justify-content: space-between; align-items: stretch; margin-bottom: 8px; }
        .emp { flex: 1; padding-right: 10px; }
        .emp h1 { font-size: 16px; margin: 0 0 3px; }
        .emp p { margin: 1px 0; }
        .caja { width: 62mm; border: 2px solid #000; text-align: center; padding: 8px 4px; }
        .caja .t { font-weight: bold; font-size: 12px; }
        .caja .n { font-weight: bold; font-size: 16px; margin-top: 6px; }
        .nota { font-size: 9px; color: #666; text-align: center; margin: 14px 0 0; }
        .aviso { border: 1px dashed #666; padding: 4px 6px; font-size: 10px; margin-bottom: 8px; text-align: center; }
        .sec { border: 1px solid #000; margin-bottom: 6px; }
        .sec > .tit { background: #e6e6e6; font-weight: bold; padding: 2px 6px; border-bottom: 1px solid #000; text-transform: uppercase; font-size: 10px; }
        .sec > .cuerpo { padding: 4px 6px; }
        .fila { display: flex; gap: 12px; }
        .fila > div { flex: 1; }
        .lbl { color: #444; font-size: 9px; text-transform: uppercase; display: block; }
        .val { display: block; margin-bottom: 3px; min-height: 12px; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th, table.items td { border: 1px solid #000; padding: 3px 5px; }
        table.items th { background: #e6e6e6; font-size: 10px; }
        .r { text-align: right; }
        .firmas { display: flex; gap: 30px; margin-top: 40px; }
        .firmas > div { flex: 1; border-top: 1px solid #000; text-align: center; padding-top: 3px; font-size: 10px; }
        .marca { position: fixed; top: 38%; left: 0; right: 0; text-align: center; font-size: 90px; color: rgba(200, 0, 0, .18); transform: rotate(-25deg); pointer-events: none; }
        .noprint { text-align: center; padding: 8px; background: #f4f4f4; border-bottom: 1px solid #ccc; }
        @media print { .noprint { display: none; } }
    </style>
</head>

<body<?= $g["estado"] !== "ANULADO" ? ' onload="window.print();"' : ''; ?>>
    <div class="noprint"><button onclick="window.print()">Imprimir</button></div>
    <?php if ($g["estado"] === "ANULADO") { ?><div class="marca">ANULADA</div><?php } ?>

    <div class="hoja">
        <div class="cab">
            <div class="emp">
                <h1><?= h(strtoupper($r["nombre"])); ?></h1>
                <p><?= h($r["direccion"] . " " . $r["urb"]); ?></p>
                <p><?= h($r["dist"] . " - " . $r["prov"] . " - " . $r["dpto"]); ?></p>
            </div>
            <div class="caja">
                <div class="t">RUC <?= h($r["doc"]); ?></div>
                <div class="t"><?= $interna ? "GUÍA DE REMISIÓN<br>INTERNA" : "GUÍA DE REMISIÓN<br>REMITENTE"; ?></div>
                <div class="n"><?= h($g["documento"]); ?></div>
            </div>
        </div>

        <div class="sec">
            <div class="tit">Datos del traslado</div>
            <div class="cuerpo">
                <div class="fila">
                    <div><span class="lbl">Fecha de emisión</span><span class="val"><?= h(fecha($g["fecha_emision"])); ?></span></div>
                    <div><span class="lbl">Inicio de traslado</span><span class="val"><?= h(fecha($g["fecha_traslado"])); ?></span></div>
                    <div><span class="lbl">Peso bruto total</span><span class="val"><?= h(num($g["peso_kg"])); ?> KGM</span></div>
                    <div><span class="lbl">Bultos</span><span class="val"><?= h($g["bultos"]); ?></span></div>
                </div>
                <span class="lbl">Motivo de traslado</span>
                <span class="val"><?= h($g["motivo_cod"] . " - " . $g["motivo_desc"]); ?></span>
                <span class="lbl">Modalidad de transporte</span>
                <span class="val"><?= $privado ? "02 - Transporte privado" : "01 - Transporte público"; ?></span>
            </div>
        </div>

        <div class="sec">
            <div class="tit">Destinatario</div>
            <div class="cuerpo">
                <div class="fila">
                    <div style="flex:2"><span class="lbl">Razón social / nombre</span><span class="val"><?= h($g["dest_nombre"]); ?></span></div>
                    <div><span class="lbl"><?= h(isset($tipoDoc[$g["dest_tipo_doc"]]) ? $tipoDoc[$g["dest_tipo_doc"]] : "Documento"); ?></span><span class="val"><?= h($g["dest_doc"]); ?></span></div>
                </div>
            </div>
        </div>

        <div class="fila" style="gap:6px">
            <div class="sec">
                <div class="tit">Punto de partida</div>
                <div class="cuerpo">
                    <span class="val"><?= h($g["par_direccion"]); ?></span>
                    <span class="val"><?= h($g["par_dist"] . " - " . $g["par_prov"] . " - " . $g["par_dpto"]); ?></span>
                    <span class="lbl">Ubigeo</span><span class="val"><?= h($g["par_ubigeo"]); ?></span>
                </div>
            </div>
            <div class="sec">
                <div class="tit">Punto de llegada</div>
                <div class="cuerpo">
                    <span class="val"><?= h($g["lle_direccion"]); ?></span>
                    <span class="val"><?= h($g["lle_dist"] . " - " . $g["lle_prov"] . " - " . $g["lle_dpto"]); ?></span>
                    <span class="lbl">Ubigeo</span><span class="val"><?= h($g["lle_ubigeo"]); ?></span>
                </div>
            </div>
        </div>

        <div class="sec">
            <div class="tit"><?= $privado ? "Conductor y vehículo" : "Transportista"; ?></div>
            <div class="cuerpo">
                <?php if ($privado) { ?>
                    <div class="fila">
                        <div style="flex:2"><span class="lbl">Conductor</span><span class="val"><?= h($g["chofer_nombres"] . " " . $g["chofer_apellidos"]); ?></span></div>
                        <div><span class="lbl"><?= h(isset($tipoDoc[$g["chofer_tipo_doc"]]) ? $tipoDoc[$g["chofer_tipo_doc"]] : "Documento"); ?></span><span class="val"><?= h($g["chofer_doc"]); ?></span></div>
                        <div><span class="lbl">Licencia</span><span class="val"><?= h($g["chofer_licencia"]); ?></span></div>
                        <div><span class="lbl">Placa</span><span class="val"><?= h($g["placa"]); ?></span></div>
                    </div>
                <?php } else { ?>
                    <div class="fila">
                        <div style="flex:2"><span class="lbl">Razón social</span><span class="val"><?= h($g["transp_nombre"]); ?></span></div>
                        <div><span class="lbl">RUC</span><span class="val"><?= h($g["transp_ruc"]); ?></span></div>
                        <div><span class="lbl">MTC</span><span class="val"><?= h($g["transp_mtc"]); ?></span></div>
                    </div>
                <?php } ?>
            </div>
        </div>

        <table class="items">
            <thead>
                <tr><th style="width:28px">N°</th><th style="width:90px">Código</th><th>Descripción</th><th style="width:70px">Unidad</th><th style="width:70px">Cantidad</th></tr>
            </thead>
            <tbody>
                <?php foreach ($g["items"] as $i => $it) { ?>
                    <tr>
                        <td class="r"><?= $i + 1; ?></td>
                        <td><?= h($it["codigo"]); ?></td>
                        <td><?= h($it["descripcion"]); ?></td>
                        <td><?= h($it["unidad_desc"] !== "" ? $it["unidad_desc"] : $it["unidad_cod"]); ?></td>
                        <td class="r"><?= h(num($it["cantidad"])); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <?php if ($docs) { ?>
            <div class="sec" style="margin-top:6px">
                <div class="tit">Documentos relacionados</div>
                <div class="cuerpo">
                    <?php foreach ($docs as $d) { ?>
                        <span class="val"><?= h((isset($tipoRel[$d["tipo"]]) ? $tipoRel[$d["tipo"]] : $d["tipo"]) . ": " . $d["numero"]); ?></span>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>

        <?php if ($g["observaciones"] !== "" && $g["observaciones"] !== null) { ?>
            <div class="sec" style="margin-top:6px">
                <div class="tit">Observaciones</div>
                <div class="cuerpo"><?= h($g["observaciones"]); ?></div>
            </div>
        <?php } ?>

        <div class="firmas">
            <div>Despachado por</div>
            <div>Transportista / Conductor</div>
            <div>Recibí conforme</div>
        </div>
        <p style="font-size:9px;color:#666;margin-top:10px">Registrado por <?= h($g["usuario_registro"]); ?> el <?= h(date("d/m/Y H:i", strtotime($g["creado_en"]))); ?></p>
        <?php if (!$interna) { ?>
            <p class="nota">Documento interno. La guía electrónica válida es la emitida por EFACT.</p>
        <?php } ?>
    </div>
</body>

</html>
