<?php
date_default_timezone_set('America/Lima');
require_once "../../controladores/cuentas.controlador.php";
require_once "../../modelos/cuentas.modelo.php";
require_once "../../extensiones/cantidad_en_letras.php";

/* Datos de entrada */
$numCuenta = isset($_GET["numCuenta"]) ? $_GET["numCuenta"] : null;
$mostrarFondo = (isset($_GET["fondo"]) && $_GET["fondo"] === "1");
$soloPlantilla = (isset($_GET["plantilla"]) && $_GET["plantilla"] === "1");
$fecha = date("d-m-Y");

if (!$soloPlantilla && $numCuenta !== null) {
    /* Traer datos */
    $respuesta = ControladorCuentas::ctrMostrarCuentasLetras("num_cta", $numCuenta);

    /* Establecer datos para la letra */
    $lugar = "LIMA";
    $referencia = isset($respuesta["referencia"]) ? $respuesta["referencia"] : "";
    $diaEmision = substr($respuesta["fecha"], 8, 2);
    $mesEmision = substr($respuesta["fecha"], 5, 2);
    $anoEmision = substr($respuesta["fecha"], 0, 4);

    $diaVencimiento = substr($respuesta["fecha_ven"], 8, 2);
    $mesVencimiento = substr($respuesta["fecha_ven"], 5, 2);
    $anoVencimiento = substr($respuesta["fecha_ven"], 0, 4);

    $letras = CantidadEnLetra($respuesta["saldo"]);

    /**
     * Divide texto en dos líneas respetando palabras.
     */
    function dividirTexto($prefijo, $texto, $maxCaracteres = 23)
    {
        $textoCompleto = $prefijo . $texto;
        if (strlen($textoCompleto) <= $maxCaracteres) {
            return ['linea1' => $textoCompleto, 'linea2' => ''];
        }
        $textoCorte = substr($textoCompleto, 0, $maxCaracteres);
        $ultimoEspacio = strrpos($textoCorte, ' ');
        if ($ultimoEspacio === false) $ultimoEspacio = $maxCaracteres;
        $linea1 = substr($textoCompleto, 0, $ultimoEspacio);
        $linea2 = trim(substr($textoCompleto, $ultimoEspacio));
        return ['linea1' => $linea1, 'linea2' => $linea2];
    }

    /**
     * Divide texto en hasta 4 líneas para dirección completa (dirección + localidad)
     */
    function dividirDireccionCompleta($direccion, $localidad, $maxCaracteres = 30)
    {
        // Concatenar dirección y localidad con la etiqueta LOCALIDAD:
        $textoCompleto = trim($direccion) . ' - ##LOCALIDAD##: ' . trim($localidad);
        $lineas = ['', '', '', ''];
        $posicion = 0;

        for ($i = 0; $i < 4; $i++) {
            if ($posicion >= strlen($textoCompleto)) break;

            $resto = substr($textoCompleto, $posicion);
            if (strlen($resto) <= $maxCaracteres) {
                $lineas[$i] = $resto;
                break;
            }

            $corte = substr($resto, 0, $maxCaracteres);
            $ultimoEspacio = strrpos($corte, ' ');
            if ($ultimoEspacio === false) $ultimoEspacio = $maxCaracteres;

            $lineas[$i] = substr($resto, 0, $ultimoEspacio);
            $posicion += $ultimoEspacio + 1;
        }

        // Reemplazar el marcador con HTML para negrita
        for ($i = 0; $i < 4; $i++) {
            $lineas[$i] = str_replace('##LOCALIDAD##', '<span class="">LOCALIDAD</span>', $lineas[$i]);
        }

        return $lineas;
    }

    /* Campos largos */
    $nombreDividido = dividirTexto("", $respuesta["nombre"], 23);
    $direccionCompleta = dividirDireccionCompleta($respuesta["direccion"], $respuesta["ubcli"], 30);

    /* Datos del aval (siempre se muestran las etiquetas) */
    $avalNombre = !empty($respuesta["aval_nombre"]) ? $respuesta["aval_nombre"] : '';
    $avalDir = !empty($respuesta["aval_dir"]) ? $respuesta["aval_dir"] : '';
    $avalLocalidad = !empty($respuesta["ubaval"]) ? $respuesta["ubaval"] : '';
    $avalRuc = !empty($respuesta["aval_ruc"]) ? $respuesta["aval_ruc"] : '';
    $avalTelf = !empty($respuesta["aval_telf"]) ? $respuesta["aval_telf"] : '';

    $avalNombreDividido = dividirTexto("", $avalNombre, 30);
    $avalDireccionCompleta = dividirDireccionCompleta($avalDir, $avalLocalidad, 30);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <title>Letra de Cambio</title>
    <style>
        /* Fuente opcional */
        @font-face {
            font-family: 'Consola';
            src: url('../../extensiones/consola.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        :root {
            --w: 21cm;
            /* tamaño físico del diseño */
            --h: 11cm;

            /* ===== Controles rápidos ===== */
            --scale: 1.00;
            /* 1.00=100%, sube si queda pequeño en A4 (1.05, 1.08, 1.10) */
            --page-x: 0mm;
            /* mueve TODO (fondo+texto) en X */
            --page-y: 0mm;
            /* mueve TODO (fondo+texto) en Y */

            --bg-x: 0mm;
            /* nudge SOLO del fondo */
            --bg-y: 0mm;

            --ox: 0mm;
            /* nudge SOLO del texto (overlay) */
            --oy: 0mm;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Vista en pantalla (útil para previsualizar) */
        .page {
            position: relative;
            width: var(--w);
            height: var(--h);
            font-family: 'Consola', 'Courier New', 'Consolas', 'Monaco', 'Menlo', monospace;
            font-size: 12px;
            letter-spacing: .5px;
            overflow: hidden;
        }

        .page.con-fondo {
            background: url("./img/fondo.png") no-repeat 0 0 / var(--w) var(--h);
        }

        .page.sin-fondo {
            background: #fff;
            border: 1px solid #ccc;
        }

        .page.page-extra {
            width: 210mm;
            height: 297mm;
            margin: 0 auto;
            border: none;
            background: #fff;
            display: flex;
            align-items: flex-start;
            justify-content: flex-start;
            padding: 20mm 0 0 30mm;
            box-sizing: border-box;
            page-break-before: always;
        }

        .page.page-extra img {
            width: 20%;
            height: auto;
            max-width: 20%;
            max-height: 100%;
            object-fit: contain;
        }

        .plantilla-fondo-img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .page.plantilla-solo-imagen {
            position: relative;
        }

        .overlay {
            position: absolute;
            inset: 0;
            transform: translate(var(--ox), var(--oy));
            transform-origin: top left;
        }

        /* A4 sin márgenes */
        @page {
            size: A4;
            margin: 0 !important;
        }

        @media print {

            html,
            body {
                width: 210mm;
                height: 297mm;
                margin: 0 !important;
                padding: 0 !important;
            }

            .page {
                position: absolute;
                left: var(--page-x);
                top: var(--page-y);

                /* tamaño físico objetivo compensado por scale */
                width: calc(210mm / var(--scale));
                height: calc(110mm / var(--scale));

                /* escalar desde la esquina superior izquierda */
                transform-origin: top left;
                transform: scale(var(--scale));
            }

            .page.con-fondo {
                background: url("./img/fondo.png") no-repeat 0 0;
                background-size: calc(210mm / var(--scale)) calc(110mm / var(--scale));
                background-position: var(--bg-x) var(--bg-y);
            }

            .page.sin-fondo {
                background: #fff;
            }

            .page.page-extra {
                position: relative;
                left: 0;
                top: 0;
                width: 210mm;
                height: 297mm;
                margin: 0;
                border: none;
                padding: 10mm 0 0 30mm;
                page-break-before: always;
                transform: none;
            }

            .overlay {
                transform: translate(var(--ox), var(--oy));
            }
        }

        /* Campos posicionados */
        .field {
            position: absolute;
            line-height: 1;
            white-space: nowrap;
            background: transparent;
            font-size: var(--fs, 12px);
            font-weight: var(--fw, 400);
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        /* Simular negrita (mejor legibilidad impresión) */
        .field[style*="--fw:500"],
        .field.bold {
            font-weight: 500;
            text-shadow: .2px 0 0 currentColor, -.2px 0 0 currentColor, 0 .2px 0 currentColor, 0 -.2px 0 currentColor, .3px 0 0 currentColor, -.3px 0 0 currentColor;
            -webkit-text-stroke: .2px currentColor;
        }

        .field[style*="--fw:700"],
        .field.bolder {
            font-weight: 700;
            text-shadow: .3px 0 0 currentColor, -.3px 0 0 currentColor, 0 .3px 0 currentColor, 0 -.3px 0 currentColor, .5px 0 0 currentColor, -.5px 0 0 currentColor;
            -webkit-text-stroke: .3px currentColor;
        }

        /* Etiquetas */
        .label-bold {
            font-weight: 200;
            text-shadow: .3px 0 0 currentColor, -.3px 0 0 currentColor, 0 .3px 0 currentColor, 0 -.3px 0 currentColor, .5px 0 0 currentColor, -.5px 0 0 currentColor;
            -webkit-text-stroke: .3px currentColor;
        }

        /* ===== Coordenadas ===== */
        #f-numero {
            left: 3.80cm;
            top: 2.00cm;
            width: 3.8cm;
        }

        #f-ref {
            left: 6.30cm;
            top: 2.00cm;
            width: 3.8cm;
        }

        #f-giro-d {
            left: 9.50cm;
            top: 2.20cm;
            width: 1.0cm;
        }

        #f-giro-m {
            left: 10.50cm;
            top: 2.20cm;
            width: 1.0cm;
        }

        #f-giro-a {
            left: 11.20cm;
            top: 2.20cm;
            width: 1.6cm;
        }

        #f-lugar {
            left: 12.50cm;
            top: 2.00cm;
            width: 2.7cm;
        }

        #f-ven-d {
            left: 15.10cm;
            top: 2.20cm;
            width: .9cm;
        }

        #f-ven-m {
            left: 15.90cm;
            top: 2.20cm;
            width: .9cm;
        }

        #f-ven-a {
            left: 16.50cm;
            top: 2.20cm;
            width: 1.6cm;
        }

        #f-moneda {
            left: 18.00cm;
            top: 2.00cm;
            width: .7cm;
        }

        #f-importe {
            left: 17.80cm;
            top: 2.00cm;
            width: 2.0cm;
        }

        #f-letras {
            left: 4.70cm;
            top: 3.35cm;
            width: 20.5cm;
        }

        #f-nombre {
            left: 4.70cm;
            top: 4.50cm;
            width: 10.7cm;
        }

        #f-nombre-linea2 {
            left: 4.70cm;
            top: 5.00cm;
            width: 10.7cm;
        }

        #f-direccion {
            left: 4.70cm;
            top: 5.50cm;
            width: 17.0cm;
        }

        #f-direccion-linea2 {
            left: 4.70cm;
            top: 5.90cm;
            width: 17.0cm;
        }

        #f-direccion-linea3 {
            left: 4.70cm;
            top: 6.30cm;
            width: 17.0cm;
        }

        #f-direccion-linea4 {
            left: 4.70cm;
            top: 6.70cm;
            width: 17.0cm;
        }

        #f-doi {
            left: 4.70cm;
            top: 7.30cm;
            width: 8.0cm;
        }

        #f-telefono {
            left: 8.50cm;
            top: 7.30cm;
            width: 8.0cm;
        }

        /* ===== Campos del Aval ===== */
        #f-aval-nombre {
            left: 4.70cm;
            top: 8.20cm;
            width: 10.7cm;
        }

        #f-aval-nombre-linea2 {
            left: 4.70cm;
            top: 8.60cm;
            width: 10.7cm;
        }

        #f-aval-direccion {
            left: 4.70cm;
            top: 8.7cm;
            width: 17.0cm;
        }

        #f-aval-direccion-linea2 {
            left: 4.70cm;
            top: 9.40cm;
            width: 17.0cm;
        }

        #f-aval-direccion-linea3 {
            left: 4.70cm;
            top: 9.80cm;
            width: 17.0cm;
        }

        #f-aval-direccion-linea4 {
            left: 4.70cm;
            top: 10.20cm;
            width: 17.0cm;
        }

        #f-aval-ruc {
            left: 4.70cm;
            top: 9.60cm;
            width: 4.0cm;
        }

        #f-aval-telefono {
            left: 6.70cm;
            top: 9.60cm;
            width: 5.0cm;
        }

        #f-aval-firma {
            left: 9.70cm;
            top: 9.60cm;
            width: 6.0cm;
        }
    </style>
</head>

<body>
    <div class="page <?php if ($mostrarFondo) {
                            echo 'con-fondo';
                        } else {
                            echo 'sin-fondo';
                        } ?> <?php echo $soloPlantilla ? 'plantilla-solo-imagen' : ''; ?>">
        <?php if ($soloPlantilla): ?>
            <img src="./img/fondo.png" alt="Plantilla Corporación Vasco" class="plantilla-fondo-img" />
        <?php endif; ?>
        <?php if (!$soloPlantilla && isset($respuesta)): ?>
            <div class="overlay">
                <!-- Fila superior -->
                <div id="f-numero" class="field center" style="--fs:13px; --fw:500;"><?php echo $respuesta["num_cta"]; ?></div>
                <div id="f-ref" class="field center" style="--fs:13px; --fw:500;"><?php echo $referencia; ?></div>

                <div id="f-giro-d" class="field center" style="--fs:12px;"><?php echo $diaEmision; ?></div>
                <div id="f-giro-m" class="field center" style="--fs:12px;"><?php echo $mesEmision; ?></div>
                <div id="f-giro-a" class="field center" style="--fs:12px;"><?php echo $anoEmision; ?></div>

                <div id="f-lugar" class="field center" style="--fs:12px; --fw:500;"><?php echo $lugar; ?></div>

                <div id="f-ven-d" class="field center" style="--fs:12px;"><?php echo $diaVencimiento; ?></div>
                <div id="f-ven-m" class="field center" style="--fs:12px;"><?php echo $mesVencimiento; ?></div>
                <div id="f-ven-a" class="field center" style="--fs:12px;"><?php echo $anoVencimiento; ?></div>

                <div id="f-moneda" class="field center" style="--fs:12px; --fw:500;">S/</div>
                <div id="f-importe" class="field right" style="--fs:12px; --fw:500;"><?php echo $respuesta["saldo"]; ?></div>

                <!-- Monto en letras -->
                <div id="f-letras" class="field" style="--fs:15px; --fw:500;"><?php echo $letras; ?></div>

                <!-- Nombre (puede tener 2 líneas) -->
                <div id="f-nombre" class="field" style="--fs:14px;">
                    <span class="label-bold">ACEPTANTE:</span> <?php echo $nombreDividido['linea1']; ?>
                </div>
                <?php if (!empty($nombreDividido['linea2'])): ?>
                    <div id="f-nombre-linea2" class="field" style="--fs:14px;"><?php echo $nombreDividido['linea2']; ?></div>
                <?php endif; ?>

                <!-- Dirección completa (dirección + localidad concatenadas en hasta 4 líneas) -->
                <div id="f-direccion" class="field" style="--fs:14px;">
                    <span class="label-bold">DIRECCION:</span> <?php echo $direccionCompleta[0]; ?>
                </div>
                <?php if (!empty($direccionCompleta[1])): ?>
                    <div id="f-direccion-linea2" class="field" style="--fs:14px;"><?php echo $direccionCompleta[1]; ?></div>
                <?php endif; ?>
                <?php if (!empty($direccionCompleta[2])): ?>
                    <div id="f-direccion-linea3" class="field" style="--fs:14px;"><?php echo $direccionCompleta[2]; ?></div>
                <?php endif; ?>
                <?php if (!empty($direccionCompleta[3])): ?>
                    <div id="f-direccion-linea4" class="field" style="--fs:14px;"><?php echo $direccionCompleta[3]; ?></div>
                <?php endif; ?>

                <!-- DOI / Teléfono -->
                <div id="f-doi" class="field" style="--fs:14px;">
                    <span class="label-bold">DOI:</span> <?php echo $respuesta["documento"]; ?>
                </div>
                <div id="f-telefono" class="field" style="--fs:14px;">
                    <span class="label-bold">TELEFONO:</span> <?php echo $respuesta["telefono"]; ?>
                </div>

                <!-- ===== DATOS DEL AVAL (siempre visible) ===== -->

                <!-- Nombre del aval permanente (puede tener 2 líneas) -->
                <div id="f-aval-nombre" class="field" style="--fs:14px;">
                    <span class="">AVAL PERMANENTE:</span> <?php echo $avalNombreDividido['linea1']; ?>
                </div>
                <?php if (!empty($avalNombreDividido['linea2'])): ?>
                    <div id="f-aval-nombre-linea2" class="field" style="--fs:14px;"><?php echo $avalNombreDividido['linea2']; ?></div>
                <?php endif; ?>

                <!-- Domicilio del aval (domicilio + localidad concatenadas en hasta 4 líneas) -->
                <div id="f-aval-direccion" class="field" style="--fs:14px;">
                    <span class="">DOMICILIO:</span> <?php echo $avalDireccionCompleta[0]; ?>
                </div>
                <?php if (!empty($avalDireccionCompleta[1])): ?>
                    <div id="f-aval-direccion-linea2" class="field" style="--fs:14px;"><?php echo $avalDireccionCompleta[1]; ?></div>
                <?php endif; ?>
                <?php if (!empty($avalDireccionCompleta[2])): ?>
                    <div id="f-aval-direccion-linea3" class="field" style="--fs:14px;"><?php echo $avalDireccionCompleta[2]; ?></div>
                <?php endif; ?>
                <?php if (!empty($avalDireccionCompleta[3])): ?>
                    <div id="f-aval-direccion-linea4" class="field" style="--fs:14px;"><?php echo $avalDireccionCompleta[3]; ?></div>
                <?php endif; ?>

                <!-- DOI y Teléfono del aval -->
                <div id="f-aval-ruc" class="field" style="--fs:14px;">
                    <span class="">DOI:</span> <?php echo $avalRuc; ?>
                </div>
                <div id="f-aval-telefono" class="field" style="--fs:14px;">
                    <span class="">TELEFONO:</span> <?php echo $avalTelf; ?>
                </div>

                <!-- Espacio para firma -->
                <div id="f-aval-firma" class="field" style="--fs:14px;">
                    <span class="">FIRMA:</span> _______
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($soloPlantilla): ?>
        <div class="page page-extra">
            <img src="./img/firma.png" alt="Firma Corporación Vasco" class="plantilla-firma-img" />
        </div>
    <?php endif; ?>
    <!-- <script>window.print();</script> -->
</body>

</html>