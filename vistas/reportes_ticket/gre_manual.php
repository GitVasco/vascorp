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
$catMotivos = ControladorGreManual::motivos();
$motivoNombre = isset($catMotivos[$g["motivo_cod"]]) ? $catMotivos[$g["motivo_cod"]] : $g["motivo_desc"];
if ($g["motivo_cod"] === "13") {
    $motivoNombre = "OTROS";
}
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
/** Cantidad con miles y decimales solo si los tiene (mínimo 2, máximo 3): 1,250 · 12.50 · 0.125 */
function num($v)
{
    $v = round((float) $v, 3);
    if (floor($v) == $v) {
        return number_format($v, 0, ".", ",");
    }
    $t = rtrim(number_format($v, 3, ".", ","), "0");
    $dec = strlen($t) - strpos($t, ".") - 1;
    return $dec < 2 ? $t . str_repeat("0", 2 - $dec) : $t;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Guía <?= h($g["documento"]); ?></title>
    <style>
        @page { size: A4; margin: 10mm; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        :root { --oscuro: #1c2c42; --acento: #3c8dbc; --claro: #eef3f8; --zebra: #f7f9fc; --linea: #c4cdd8; --ambar: #fff3cd; --ambar-t: #966000; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1b1b1b; margin: 0; }
        .hoja { max-width: 190mm; margin: 0 auto; position: relative; }

        /* Banda superior */
        .banda { background: var(--oscuro); color: #fff; padding: 11px 14px; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid var(--acento); }
        .banda .emp b { font-size: 15px; letter-spacing: .3px; display: block; margin-bottom: 3px; }
        .banda .emp span { display: block; color: #bed2e4; font-size: 9.5px; line-height: 1.5; }
        .banda .doc { text-align: right; }
        .banda .doc small { display: block; font-size: 9px; font-weight: bold; letter-spacing: .6px; color: #bed2e4; text-transform: uppercase; }
        .banda .doc strong { display: block; font-size: 21px; margin-top: 2px; }

        .nota { background: var(--claro); color: #4a5a6c; padding: 5px 9px; font-size: 9.5px; margin: 6px 0; text-align: center; }

        /* Secciones */
        .sec { border: 1px solid var(--linea); margin-bottom: 7px; }
        .sec > .tit { background: var(--oscuro); color: #fff; font-weight: bold; padding: 4px 9px; text-transform: uppercase; font-size: 9.5px; letter-spacing: .4px; }
        .sec > .cuerpo { padding: 5px 9px 3px; }
        .fila { display: flex; gap: 14px; }
        .fila > div { flex: 1; }
        .fila.dos { gap: 7px; }
        .lbl { color: #5b6b7e; font-size: 8.5px; text-transform: uppercase; letter-spacing: .3px; display: block; }
        .val { display: block; margin-bottom: 4px; min-height: 12px; font-weight: bold; }
        .val.n { font-weight: normal; }

        /* Tablas */
        table.items { width: 100%; border-collapse: collapse; margin-top: 2px; }
        table.items th { background: #34495e; color: #fff; font-size: 9.5px; padding: 4px 5px; border: 1px solid #34495e; }
        table.items td { border: 1px solid var(--linea); padding: 3px 5px; }
        table.items tbody tr:nth-child(even) { background: var(--zebra); }
        .r { text-align: right; }
        .totalbar { background: var(--oscuro); color: #fff; padding: 7px 12px; display: flex; justify-content: space-between; align-items: center; margin-top: 6px; font-weight: bold; }
        
        .firmas { display: flex; gap: 30px; margin-top: 46px; }
        .firmas > div { flex: 1; border-top: 1px solid var(--oscuro); text-align: center; padding-top: 3px; font-size: 10px; color: var(--oscuro); }
        .pie { font-size: 9px; color: #6b7785; margin: 10px 0 0; }
        .marca { position: fixed; top: 38%; left: 0; right: 0; text-align: center; font-size: 90px; color: rgba(200, 0, 0, .18); transform: rotate(-25deg); pointer-events: none; }
        .noprint { text-align: center; padding: 8px; background: #f4f4f4; border-bottom: 1px solid #ccc; }
        @media print { .noprint { display: none; } }
    </style>
</head>

<body<?= $g["estado"] !== "ANULADO" ? ' onload="window.print();"' : ''; ?>>
    <div class="noprint"><button onclick="window.print()">Imprimir</button></div>
    <?php if ($g["estado"] === "ANULADO") { ?><div class="marca">ANULADA</div><?php } ?>

    <div class="hoja">
        <div class="banda">
            <div class="emp">
                <b><?= h(strtoupper($r["nombre"])); ?></b>
                <span>RUC <?= h($r["doc"]); ?></span>
                <span><?= h($r["direccion"] . " " . $r["urb"] . " · " . $r["dist"] . " - " . $r["prov"] . " - " . $r["dpto"]); ?></span>
            </div>
            <div class="doc">
                <small><?= $interna ? "Guía de remisión interna" : "Guía de remisión remitente"; ?></small>
                <strong><?= h($g["documento"]); ?></strong>
            </div>
        </div>

        <div style="height:8px"></div>

        <div class="sec">
            <div class="tit">Datos del traslado</div>
            <div class="cuerpo">
                <div class="fila">
                    <div><span class="lbl">Fecha de emisión</span><span class="val"><?= h(fecha($g["fecha_emision"])); ?></span></div>
                    <div><span class="lbl">Inicio de traslado</span><span class="val"><?= h(fecha($g["fecha_traslado"])); ?></span></div>
                    <div><span class="lbl">Peso bruto total</span><span class="val"><?= h(num($g["peso_kg"])); ?> KGM</span></div>
                    <div><span class="lbl">Bultos</span><span class="val"><?= h($g["bultos"]); ?></span></div>
                </div>
                <div class="fila">
                    <div><span class="lbl">Motivo de traslado</span><span class="val"><?= h($g["motivo_cod"] . " - " . $motivoNombre); ?></span></div>
                    <div><span class="lbl">Modalidad de transporte</span><span class="val"><?= $privado ? "02 - Transporte privado" : "01 - Transporte público"; ?></span></div>
                </div>
                <?php if (trim($g["motivo_desc"]) !== "" && strcasecmp(trim($g["motivo_desc"]), $motivoNombre) !== 0) { ?>
                    <span class="lbl">Descripción</span>
                    <span class="val n"><?= h($g["motivo_desc"]); ?></span>
                <?php } ?>
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

        <div class="fila dos">
            <div class="sec">
                <div class="tit">Punto de partida</div>
                <div class="cuerpo">
                    <span class="val"><?= h($g["par_direccion"]); ?></span>
                    <span class="val n"><?= h($g["par_dist"] . " - " . $g["par_prov"] . " - " . $g["par_dpto"]); ?></span>
                    <span class="lbl">Ubigeo</span><span class="val n"><?= h($g["par_ubigeo"]); ?></span>
                </div>
            </div>
            <div class="sec">
                <div class="tit">Punto de llegada</div>
                <div class="cuerpo">
                    <span class="val"><?= h($g["lle_direccion"]); ?></span>
                    <span class="val n"><?= h($g["lle_dist"] . " - " . $g["lle_prov"] . " - " . $g["lle_dpto"]); ?></span>
                    <span class="lbl">Ubigeo</span><span class="val n"><?= h($g["lle_ubigeo"]); ?></span>
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
        <?php
        $unidadesItems = array();
        $sumaItems = 0;
        foreach ($g["items"] as $it) {
            $unidadesItems[$it["unidad_cod"]] = true;
            $sumaItems += (float) $it["cantidad"];
        }
        if (count($g["items"]) > 1 && count($unidadesItems) === 1) { ?>
            <div class="totalbar"><span>TOTAL (<?= count($g["items"]); ?> ítems)</span><span><?= h(num($sumaItems)); ?></span></div>
        <?php } ?>

        <?php if ($docs) { ?>
            <div class="sec" style="margin-top:7px">
                <div class="tit">Documentos relacionados</div>
                <div class="cuerpo">
                    <?php foreach ($docs as $d) { ?>
                        <span class="val n"><?= h((isset($tipoRel[$d["tipo"]]) ? $tipoRel[$d["tipo"]] : $d["tipo"]) . ": " . $d["numero"]); ?></span>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>

        <?php if ($g["observaciones"] !== "" && $g["observaciones"] !== null) { ?>
            <div class="sec" style="margin-top:7px">
                <div class="tit">Observaciones</div>
                <div class="cuerpo"><span class="val n"><?= h($g["observaciones"]); ?></span></div>
            </div>
        <?php } ?>

        <div class="firmas">
            <div>Despachado por</div>
            <div>Transportista / Conductor</div>
            <div>Recibí conforme</div>
        </div>
        <p class="pie">Registrado por <?= h($g["usuario_registro"]); ?> el <?= h(date("d/m/Y H:i", strtotime($g["creado_en"]))); ?></p>
        <?php if (!$interna) { ?>
            <div class="nota">Representación impresa de la Guía de Remisión Electrónica Remitente. Puede consultar su validez en el portal de SUNAT.</div>
        <?php } ?>
    </div>

</body>

</html>
