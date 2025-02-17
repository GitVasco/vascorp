<?php

class ControladorContabilidad
{

    static public function eliminar_tildes($cadena)
    {

        //Codificamos la cadena en formato utf8 en caso de que nos de errores
        #$cadena = utf8_decode($cadena);

        //Ahora reemplazamos las letras
        $cadena = str_replace(
            array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
            array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
            $cadena
        );

        $cadena = str_replace(
            array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
            array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
            $cadena
        );

        $cadena = str_replace(
            array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
            array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
            $cadena
        );

        $cadena = str_replace(
            array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
            array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
            $cadena
        );

        $cadena = str_replace(
            array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
            array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
            $cadena
        );

        $cadena = str_replace(
            array('ñ', 'Ñ', 'ç', 'Ç'),
            array('n', 'N', 'c', 'C'),
            $cadena
        );

        return $cadena;
    }

    static public function ctrGenerarVentasSiscont()
    {

        if (isset($_POST["inicioSiscont"])) {

            #var_dump($_POST["inicioSiscont"]);

            $fechaInicio = $_POST["inicioSiscont"];
            $fechaFin = $_POST["finSiscont"];

            $añoI = date("Y", strtotime($fechaInicio));
            $mesI = date("m", strtotime($fechaInicio));

            $fi = str_replace("-", "", $fechaInicio);
            $ff = str_replace("-", "", $fechaFin);

            $nomar = $fi . $ff;
            #var_dump($nomar);

            $ruta = "vistas/contabilidad/ventas/V$fi$ff.txt";
            #var_dump($ruta);

            $archivo = fopen($ruta, "w");

            #$ventas = ModeloContabilidad::mdlVentasConfiguradas($fechaInicio, $fechaFin);
            $ventas = ModeloContabilidad::mdlVentasSiscontB($fechaInicio, $fechaFin);
            $voucher = ModeloContabilidad::mdlVoucherSiscont($añoI, $mesI);
            #var_dump($ventas);

            $corr = $_POST["iniVentas"];

            for ($i = 0; $i < count($ventas); $i++) {

                if ($ventas[$i]["documento"] == $ventas[$i - 1]["documento"]) {

                    $corr;
                } else {

                    $corr++;
                }

                /* $nom_cliente = ControladorContabilidad::eliminar_tildes($ventas[$i]["nom_cliente"]);
                $ape_paterno = ControladorContabilidad::eliminar_tildes($ventas[$i]["ape_paterno"]);
                $ape_materno = ControladorContabilidad::eliminar_tildes($ventas[$i]["ape_materno"]);
                $nombres = ControladorContabilidad::eliminar_tildes($ventas[$i]["nombres"]);
                $nombre1 = explode(" ", $nombres);    */

                $origen     = str_pad($ventas[$i]["t"], 2);
                $voucher    = str_pad($corr, 5, '0', STR_PAD_LEFT);
                $fecha      = str_pad($ventas[$i]["fecha"], 8);
                $cuenta     = str_pad($ventas[$i]["cuenta"], 10);
                $debe       = str_pad($ventas[$i]["debe"], 12, '0', STR_PAD_LEFT);
                $haber      = str_pad($ventas[$i]["haber"], 12, '0', STR_PAD_LEFT);
                $moneda     = str_pad($ventas[$i]["moneda"], 1);
                $tc         = str_pad($ventas[$i]["tc"], 10, '0', STR_PAD_LEFT);
                $doc        = str_pad($ventas[$i]["doc"], 2);
                $numero     = str_pad($ventas[$i]["numero"], 40);
                $fechad     = str_pad($ventas[$i]["fechad"], 8);
                $fechav     = str_pad($ventas[$i]["fechav"], 8);
                $codigo     = str_pad($ventas[$i]["codigo"], 15);
                $cc         = str_pad(" ", 10);
                $fe         = str_pad(" ", 4);
                $pre        = str_pad(" ", 10);
                $mpago      = str_pad($ventas[$i]["mpago"], 3);
                $glosa      = str_pad($ventas[$i]["glosa"], 60);
                $rnumero    = str_pad($ventas[$i]["rnumero"], 40);
                $rtdoc      = str_pad($ventas[$i]["rtdoc"], 2);
                $rfecha     = str_pad($ventas[$i]["rfecha"], 8);
                $snumero    = str_pad(" ", 40);
                $sfecha     = str_pad(" ", 8);
                $tl         = str_pad("V", 1);
                $neto       = str_pad($ventas[$i]["neto"], 12, '0', STR_PAD_LEFT);
                $neto2      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto3      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto4      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $igv        = str_pad($ventas[$i]["igv"], 12, '0', STR_PAD_LEFT);
                $neto5      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto6      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto7      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto8      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                #$neto9      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                /* $ruc        = str_pad($ventas[$i]["ruc"], 15);
                $tipo       = str_pad($ventas[$i]["tip_cli"], 1);
                $r5         = str_pad($nom_cliente, 60);
                $ape1       = str_pad($ape_paterno, 20);
                $ape2       = str_pad($ape_materno, 20);
                $nombre     = str_pad($nombre1[0], 20);
                $tdoi       = str_pad($ventas[$i]["tipo_documento"], 1); */
                $ruc        = str_pad(" ", 15);
                $tipo       = str_pad(" ", 1);
                $r5         = str_pad(" ", 60);
                $ape1       = str_pad(" ", 20);
                $ape2       = str_pad(" ", 20);
                $nombre     = str_pad(" ", 20);
                $tdoi       = str_pad(" ", 1);
                $rnumdes    = str_pad(" ", 1);
                $rcodtasa   = str_pad(" ", 5);
                $rindret    = str_pad(" ", 1);
                $rmonto     = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $rigv       = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $tbien      = str_pad(" ", 1);


                fwrite($archivo,    $origen .
                    $voucher .
                    $fecha .
                    $cuenta .
                    $debe .
                    $haber .
                    $moneda .
                    $tc .
                    $doc .
                    $numero .
                    $fechad .
                    $fechav .
                    $codigo .
                    $cc .
                    $fe .
                    $pre .
                    $mpago .
                    $glosa .
                    $rnumero .
                    $rtdoc .
                    $rfecha .
                    $snumero .
                    $sfecha .
                    $tl .
                    $neto .
                    $neto2 .
                    $neto3 .
                    $neto4 .
                    $igv .
                    $neto5 .
                    $neto6 .
                    $neto7 .
                    $neto8 .
                    $ruc .
                    $tipo .
                    $r5 .
                    $ape1 .
                    $ape2 .
                    $nombre .
                    $tdoi .
                    $rnumdes .
                    $rcodtasa .
                    $rindret .
                    $rmonto .
                    $rigv .
                    $tbien .
                    PHP_EOL);
            }

            fclose($archivo);

            $origen = 'c:/xampp2/htdocs/vascorp/vistas/contabilidad/ventas/V' . $nomar . '.txt';

            #$destino = '//Sistemas-2/d/contabilidad/ventas/V'.$nomar.'.txt';   
            $destino = '//Yudy-pc/datasmart/VASCO2025/V' . $nomar . '.txt';

            copy($origen, $destino);
            #rename($origen, $destino);

            $rutaBat = "vistas/contabilidad/ventas/VB$fi$ff.bat";
            $archivoBat = fopen($rutaBat, "w");

            $nombreEmpresa = "VASCO2025";

            fwrite($archivoBat, "MSISCONT.EXE " . $nombreEmpresa . " V" . $nomar . ".txt" . PHP_EOL);
            fclose($archivoBat);

            $origen2 = 'c:/xampp2/htdocs/vascorp/vistas/contabilidad/ventas/VB' . $nomar . '.bat';
            #$destino2 = '//Sistemas-2/d/contabilidad/ventas/VB'.$nomar.'.bat';
            $destino2 = '//Yudy-pc/datasmart/VASCO2025/VB' . $nomar . '.bat';
            copy($origen2, $destino2);
            #rename($origen, $destino);

            #var_dump($corr);

            $correlativo = ModeloContabilidad::mdlActualizarCorrelativo($añoI, $mesI, $corr, "valor_1");
            #var_dump($correlativo);

            if ($correlativo == "ok") {

                echo '<script>

                swal({
                    type: "success",
                    title: "Se genero el archivo correctamente",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar",
                    closeOnConfirm: false
                    }).then(function(result){
                        if (result.value) {

                        window.location = "procesar-ce";

                        }
                    })
    
                </script>';
            }
        }
    }

    static public function ctrGenerarCanjeSiscont()
    {

        if (isset($_POST["inicioSiscontL"])) {

            #var_dump($_POST["inicioSiscontL"]);

            $fechaInicio = $_POST["inicioSiscontL"];
            $fechaFin = $_POST["finSiscontL"];

            $añoI = date("Y", strtotime($fechaInicio));
            $mesI = date("m", strtotime($fechaInicio));

            $fi = str_replace("-", "", $fechaInicio);
            $ff = str_replace("-", "", $fechaFin);

            $nomar = $fi . $ff;
            #var_dump($nomar);

            $ruta = "vistas/contabilidad/letras/L$fi$ff.txt";
            #var_dump($ruta);

            $archivo = fopen($ruta, "w");

            $letras = ModeloContabilidad::mdlLetrasConfiguradas($fechaInicio, $fechaFin);
            $voucher = ModeloContabilidad::mdlVoucherSiscont($añoI, $mesI);
            #var_dump($letras);

            $corr = $_POST["iniCanje"];
            #var_dump($corr);

            foreach ($letras as $key => $value) {

                $corr++;

                $documento = ModeloContabilidad::mdlLetrasSiscont($value["doc_origen"]);

                foreach ($documento as $key => $value2) {

                    #var_dump($value2["debe"]);

                    $origen     = str_pad($value2["t"], 2);
                    $voucher    = str_pad($corr, 5, '0', STR_PAD_LEFT);
                    $fecha      = str_pad($value2["fecha"], 8);
                    $cuenta     = str_pad($value2["cuenta"], 10);
                    $debe       = str_pad($value2["debe"], 12, '0', STR_PAD_LEFT);
                    $haber      = str_pad($value2["haber"], 12, '0', STR_PAD_LEFT);
                    $moneda     = str_pad($value2["moneda"], 1);
                    $tc         = str_pad($value2["tc"], 10, '0', STR_PAD_LEFT);
                    $doc        = str_pad($value2["doc"], 2);
                    $numero     = str_pad($value2["numero"], 40);
                    $fechad     = str_pad($value2["fechad"], 8);
                    $fechav     = str_pad($value2["fechav"], 8);
                    $codigo     = str_pad($value2["codigo"], 15);
                    $cc         = str_pad(" ", 10);
                    $fe         = str_pad(" ", 4);
                    $pre        = str_pad(" ", 10);
                    $mpago      = str_pad(" ", 3);
                    $glosa      = str_pad($value2["glosa"], 60);
                    $rnumero    = str_pad(" ", 40);
                    $rtdoc      = str_pad(" ", 2);
                    $rfecha     = str_pad(" ", 8);
                    $snumero    = str_pad(" ", 40);
                    $sfecha     = str_pad(" ", 8);
                    $tl         = str_pad("V", 1);
                    $neto       = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    $neto2      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    $neto3      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    $neto4      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    $igv        = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    $neto5      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    $neto6      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    $neto7      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    $neto8      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    #$neto9      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    $ruc        = str_pad(" ", 15);
                    $tipo       = str_pad(" ", 1);
                    $r5         = str_pad(" ", 60);
                    $ape1       = str_pad(" ", 20);
                    $ape2       = str_pad(" ", 20);
                    $nombre     = str_pad(" ", 20);
                    $tdoi       = str_pad(" ", 1);
                    $rnumdes    = str_pad(" ", 1);
                    $rcodtasa   = str_pad(" ", 5);
                    $rindret    = str_pad(" ", 1);
                    $rmonto     = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    $rigv       = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    $tbien      = str_pad(" ", 1);

                    fwrite($archivo,    $origen .
                        $voucher .
                        $fecha .
                        $cuenta .
                        $debe .
                        $haber .
                        $moneda .
                        $tc .
                        $doc .
                        $numero .
                        $fechad .
                        $fechav .
                        $codigo .
                        $cc .
                        $fe .
                        $pre .
                        $mpago .
                        $glosa .
                        $rnumero .
                        $rtdoc .
                        $rfecha .
                        $snumero .
                        $sfecha .
                        $tl .
                        $neto .
                        $neto2 .
                        $neto3 .
                        $neto4 .
                        $igv .
                        $neto5 .
                        $neto6 .
                        $neto7 .
                        $neto8 .
                        $ruc .
                        $tipo .
                        $r5 .
                        $ape1 .
                        $ape2 .
                        $nombre .
                        $tdoi .
                        $rnumdes .
                        $rcodtasa .
                        $rindret .
                        $rmonto .
                        $rigv .
                        $tbien .
                        PHP_EOL);
                }
            }

            //$correlativo = ModeloContabilidad::mdlActualizarCorrelativo($añoI, $mesI, $corr, "valor_2");

            $letrasB = ModeloContabilidad::mdlLetrasConfiguradasB($fechaInicio, $fechaFin);
            $voucherB = ModeloContabilidad::mdlVoucherSiscont($añoI, $mesI);
            #var_dump($letrasB);

            #$corrB = $voucherB["correlativoL"];
            #var_dump("inicio", $corr);       

            foreach ($letrasB as $key => $value) {

                $corr++;

                $documento = ModeloContabilidad::mdlLetrasSiscontB($value["cliente"], $fechaInicio, $fechaFin);

                #var_dump($value["cliente"], $fechaInicio, $fechaFin);

                foreach ($documento as $key => $value2) {

                    $origen     = str_pad($value2["t"], 2);
                    $voucher    = str_pad($corr, 5, '0', STR_PAD_LEFT);
                    $fecha      = str_pad($value2["fecha"], 8);
                    $cuenta     = str_pad($value2["cuenta"], 10);
                    $debe       = str_pad($value2["debe"], 12, '0', STR_PAD_LEFT);
                    $haber      = str_pad($value2["haber"], 12, '0', STR_PAD_LEFT);
                    $moneda     = str_pad($value2["moneda"], 1);
                    $tc         = str_pad($value2["tc"], 10, '0', STR_PAD_LEFT);
                    $doc        = str_pad($value2["doc"], 2);
                    $numero     = str_pad($value2["numero"], 40);
                    $fechad     = str_pad($value2["fechad"], 8);
                    $fechav     = str_pad($value2["fechav"], 8);
                    $codigo     = str_pad($value2["codigo"], 15);
                    $cc         = str_pad(" ", 10);
                    $fe         = str_pad(" ", 4);
                    $pre        = str_pad(" ", 10);
                    $mpago      = str_pad(" ", 3);
                    $glosa      = str_pad($value2["glosa"], 60);
                    $rnumero    = str_pad(" ", 40);
                    $rtdoc      = str_pad(" ", 2);
                    $rfecha     = str_pad(" ", 8);
                    $snumero    = str_pad(" ", 40);
                    $sfecha     = str_pad(" ", 8);
                    $tl         = str_pad("V", 1);
                    $neto       = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    $neto2      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    $neto3      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    $neto4      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    $igv        = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    $neto5      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    $neto6      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    $neto7      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    $neto8      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    #$neto9      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    $ruc        = str_pad(" ", 15);
                    $tipo       = str_pad(" ", 1);
                    $r5         = str_pad(" ", 60);
                    $ape1       = str_pad(" ", 20);
                    $ape2       = str_pad(" ", 20);
                    $nombre     = str_pad(" ", 20);
                    $tdoi       = str_pad(" ", 1);
                    $rnumdes    = str_pad(" ", 1);
                    $rcodtasa   = str_pad(" ", 5);
                    $rindret    = str_pad(" ", 1);
                    $rmonto     = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    $rigv       = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                    $tbien      = str_pad(" ", 1);

                    fwrite($archivo,    $origen .
                        $voucher .
                        $fecha .
                        $cuenta .
                        $debe .
                        $haber .
                        $moneda .
                        $tc .
                        $doc .
                        $numero .
                        $fechad .
                        $fechav .
                        $codigo .
                        $cc .
                        $fe .
                        $pre .
                        $mpago .
                        $glosa .
                        $rnumero .
                        $rtdoc .
                        $rfecha .
                        $snumero .
                        $sfecha .
                        $tl .
                        $neto .
                        $neto2 .
                        $neto3 .
                        $neto4 .
                        $igv .
                        $neto5 .
                        $neto6 .
                        $neto7 .
                        $neto8 .
                        $ruc .
                        $tipo .
                        $r5 .
                        $ape1 .
                        $ape2 .
                        $nombre .
                        $tdoi .
                        $rnumdes .
                        $rcodtasa .
                        $rindret .
                        $rmonto .
                        $rigv .
                        $tbien .
                        PHP_EOL);
                }

                #var_dump($corrB);

            }

            fclose($archivo);

            $origen = 'c:/xampp2/htdocs/vascorp/vistas/contabilidad/letras/L' . $nomar . '.txt';

            #$destino = '//Sistemas-2/d/contabilidad/letras/L'.$nomar.'.txt';   
            $destino = '//Yudy-pc/datasmart/VASCO2025/L' . $nomar . '.txt';

            copy($origen, $destino);

            $rutaBat = "vistas/contabilidad/letras/LB$fi$ff.bat";
            $archivoBat = fopen($rutaBat, "w");

            $nombreEmpresa = "VASCO2025";

            fwrite($archivoBat, "MSISCONT.EXE " . $nombreEmpresa . " L" . $nomar . ".txt" . PHP_EOL);
            fclose($archivoBat);

            $origen2 = 'c:/xampp2/htdocs/vascorp/vistas/contabilidad/letras/LB' . $nomar . '.bat';
            #$destino2 = '//Sistemas-2/d/contabilidad/letras/LB'.$nomar.'.bat';
            $destino2 = '//Yudy-pc/datasmart/VASCO2025/LB' . $nomar . '.bat';
            copy($origen2, $destino2);

            #var_dump($corr);

            #$correlativo = ModeloContabilidad::mdlActualizarCorrelativo($añoI, $mesI, $corrB, "valor_2");
            #var_dump($correlativo);


            echo '<script>
                    swal({
                        type: "success",
                        title: "Se genero el archivo correctamente",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar",
                        closeOnConfirm: false
                        }).then(function(result){
                            if (result.value) {

                            window.location = "procesar-ce";

                            }
                        })
        
                    </script>';
        }
    }

    static public function ctrGenerarCancelacionesSiscont()
    {

        if (isset($_POST["inicioSiscontC"])) {

            #var_dump($_POST["inicioSiscontC"]);

            $fechaInicio = $_POST["inicioSiscontC"];
            $fechaFin = $_POST["finSiscontC"];

            $añoI = date("Y", strtotime($fechaInicio));
            $mesI = date("m", strtotime($fechaInicio));

            $fi = str_replace("-", "", $fechaInicio);
            $ff = str_replace("-", "", $fechaFin);

            $nomar = $fi . $ff;
            #var_dump($nomar);

            $ruta = "vistas/contabilidad/cancelaciones/C$fi$ff.txt";
            #var_dump($ruta);

            $archivo = fopen($ruta, "w");

            $cancelaciones04 = ModeloContabilidad::mdlCancelacionesSiscont04($fechaInicio, $fechaFin);
            $cancelaciones08 = ModeloContabilidad::mdlCancelacionesSiscont08($fechaInicio, $fechaFin);
            $voucher = ModeloContabilidad::mdlVoucherSiscont($añoI, $mesI);
            #var_dump($cancelaciones04);   

            $corr04 = $_POST["iniCance04"];
            $corr08 = $_POST["iniCance08"];

            for ($i = 0; $i < count($cancelaciones04); $i++) {

                if ($cancelaciones04[$i]["num_cta"] == $cancelaciones04[$i - 1]["num_cta"]) {

                    $corr04;
                } else {

                    $corr04++;
                }

                if ($cancelaciones04[$i]["doc"] == 'LE') {

                    $docFormato = $cancelaciones04[$i]["numero"];
                } else {


                    $docFormato = substr($cancelaciones04[$i]["numero"], 0, 4) . '-' . substr($cancelaciones04[$i]["numero"], 4, 8);
                }

                /* $cuentas = array('00','05','06','14','80','82','TR');

                if(in_array($cancelaciones04[$i]["cod_pago"], $cuentas)){
                    $codFin = 'O101';
                }else {
                    $codFin = "";
                } */

                $cuentas = array('101100');

                if (in_array($cancelaciones04[$i]["cuenta"], $cuentas)) {
                    $codFin = 'O101';
                } else {
                    $codFin = "";
                }

                $origen     = str_pad('04', 2);
                $voucher    = str_pad($corr04, 5, '0', STR_PAD_LEFT);
                $fecha      = str_pad($cancelaciones04[$i]["fecha"], 8);
                $cuenta     = str_pad($cancelaciones04[$i]["cuenta"], 10);
                $debe       = str_pad($cancelaciones04[$i]["debe"], 12, '0', STR_PAD_LEFT);
                $haber      = str_pad($cancelaciones04[$i]["haber"], 12, '0', STR_PAD_LEFT);
                $moneda     = str_pad($cancelaciones04[$i]["moneda"], 1);
                $tc         = str_pad($cancelaciones04[$i]["tc"], 10, '0', STR_PAD_LEFT);
                $doc        = str_pad($cancelaciones04[$i]["doc"], 2);
                $numero     = str_pad($docFormato, 40);
                $fechad     = str_pad($cancelaciones04[$i]["fechad"], 8);
                $fechav     = str_pad($cancelaciones04[$i]["fechav"], 8);
                $codigo     = str_pad($cancelaciones04[$i]["codigo"], 15);
                $cc         = str_pad(" ", 10);
                $fe         = str_pad($codFin, 4);
                $pre        = str_pad(" ", 10);
                $mpago      = str_pad(" ", 3);
                $glosa      = str_pad($cancelaciones04[$i]["glosa"], 60);
                $rnumero    = str_pad(" ", 40);
                $rtdoc      = str_pad(" ", 2);
                $rfecha     = str_pad(" ", 8);
                $snumero    = str_pad(" ", 40);
                $sfecha     = str_pad(" ", 8);
                $tl         = str_pad("V", 1);
                $neto       = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto2      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto3      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto4      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $igv        = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto5      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto6      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto7      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto8      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $ruc        = str_pad(" ", 15);
                $tipo       = str_pad(" ", 1);
                $r5         = str_pad(" ", 60);
                $ape1       = str_pad(" ", 20);
                $ape2       = str_pad(" ", 20);
                $nombre     = str_pad(" ", 20);
                $tdoi       = str_pad(" ", 1);
                $rnumdes    = str_pad(" ", 1);
                $rcodtasa   = str_pad(" ", 5);
                $rindret    = str_pad(" ", 1);
                $rmonto     = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $rigv       = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $tbien      = str_pad(" ", 1);

                fwrite($archivo,    $origen .
                    $voucher .
                    $fecha .
                    $cuenta .
                    $debe .
                    $haber .
                    $moneda .
                    $tc .
                    $doc .
                    $numero .
                    $fechad .
                    $fechav .
                    $codigo .
                    $cc .
                    $fe .
                    $pre .
                    $mpago .
                    $glosa .
                    $rnumero .
                    $rtdoc .
                    $rfecha .
                    $snumero .
                    $sfecha .
                    $tl .
                    $neto .
                    $neto2 .
                    $neto3 .
                    $neto4 .
                    $igv .
                    $neto5 .
                    $neto6 .
                    $neto7 .
                    $neto8 .
                    $ruc .
                    $tipo .
                    $r5 .
                    $ape1 .
                    $ape2 .
                    $nombre .
                    $tdoi .
                    $rnumdes .
                    $rcodtasa .
                    $rindret .
                    $rmonto .
                    $rigv .
                    $tbien .
                    PHP_EOL);
            }

            for ($i = 0; $i < count($cancelaciones08); $i++) {

                if ($cancelaciones08[$i]["num_cta"] == $cancelaciones08[$i - 1]["num_cta"]) {

                    $corr08;
                } else {

                    $corr08++;
                }

                if ($cancelaciones08[$i]["doc"] == 'LE') {

                    $docFormato08 = $cancelaciones08[$i]["numero"];
                } else {


                    $docFormato08 = substr($cancelaciones08[$i]["numero"], 0, 4) . '-' . substr($cancelaciones08[$i]["numero"], 4, 8);
                }

                /* $cuentas = array('00','05','06','14','80','82','TR');

                if(in_array($cancelaciones08[$i]["cod_pago"], $cuentas)){
                    $codFin = 'O101';
                }else {
                    $codFin = "";
                }  */

                // TODO: Yudy pide quitar el codigo 101100 y agregar 104103
                // FIXME: YUDY PIDE AGREGAR EL CODIGO 101100
                $cuentas = array('104101', '104103', '104105');

                if (in_array($cancelaciones08[$i]["cuenta"], $cuentas)) {
                    $codFin = 'O101';
                } else {
                    $codFin = "";
                }

                $origen     = str_pad('08', 2);
                $voucher    = str_pad($corr08, 5, '0', STR_PAD_LEFT);
                $fecha      = str_pad($cancelaciones08[$i]["fecha"], 8);
                $cuenta     = str_pad($cancelaciones08[$i]["cuenta"], 10);
                $debe       = str_pad($cancelaciones08[$i]["debe"], 12, '0', STR_PAD_LEFT);
                $haber      = str_pad($cancelaciones08[$i]["haber"], 12, '0', STR_PAD_LEFT);
                $moneda     = str_pad($cancelaciones08[$i]["moneda"], 1);
                $tc         = str_pad($cancelaciones08[$i]["tc"], 10, '0', STR_PAD_LEFT);
                $doc        = str_pad($cancelaciones08[$i]["doc"], 2);
                $numero     = str_pad($docFormato08, 40);
                $fechad     = str_pad($cancelaciones08[$i]["fechad"], 8);
                $fechav     = str_pad($cancelaciones08[$i]["fechav"], 8);
                $codigo     = str_pad($cancelaciones08[$i]["codigo"], 15);
                $cc         = str_pad(" ", 10);
                $fe         = str_pad($codFin, 4);
                $pre        = str_pad(" ", 10);
                $mpago      = str_pad(" ", 3);
                $glosa      = str_pad($cancelaciones08[$i]["glosa"], 60);
                $rnumero    = str_pad(" ", 40);
                $rtdoc      = str_pad(" ", 2);
                $rfecha     = str_pad(" ", 8);
                $snumero    = str_pad(" ", 40);
                $sfecha     = str_pad(" ", 8);
                $tl         = str_pad("V", 1);
                $neto       = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto2      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto3      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto4      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $igv        = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto5      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto6      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto7      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto8      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                #$neto9      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $ruc        = str_pad(" ", 15);
                $tipo       = str_pad(" ", 1);
                $r5         = str_pad(" ", 60);
                $ape1       = str_pad(" ", 20);
                $ape2       = str_pad(" ", 20);
                $nombre     = str_pad(" ", 20);
                $tdoi       = str_pad(" ", 1);
                $rnumdes    = str_pad(" ", 1);
                $rcodtasa   = str_pad(" ", 5);
                $rindret    = str_pad(" ", 1);
                $rmonto     = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $rigv       = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $tbien      = str_pad(" ", 1);

                fwrite($archivo,    $origen .
                    $voucher .
                    $fecha .
                    $cuenta .
                    $debe .
                    $haber .
                    $moneda .
                    $tc .
                    $doc .
                    $numero .
                    $fechad .
                    $fechav .
                    $codigo .
                    $cc .
                    $fe .
                    $pre .
                    $mpago .
                    $glosa .
                    $rnumero .
                    $rtdoc .
                    $rfecha .
                    $snumero .
                    $sfecha .
                    $tl .
                    $neto .
                    $neto2 .
                    $neto3 .
                    $neto4 .
                    $igv .
                    $neto5 .
                    $neto6 .
                    $neto7 .
                    $neto8 .
                    $ruc .
                    $tipo .
                    $r5 .
                    $ape1 .
                    $ape2 .
                    $nombre .
                    $tdoi .
                    $rnumdes .
                    $rcodtasa .
                    $rindret .
                    $rmonto .
                    $rigv .
                    $tbien .
                    PHP_EOL);
            }

            fclose($archivo);

            $origen = 'c:/xampp2/htdocs/vascorp/vistas/contabilidad/cancelaciones/C' . $nomar . '.txt';

            #$destino = '//Sistemas-2/d/contabilidad/cancelaciones/C'.$nomar.'.txt';   
            $destino = '//Yudy-pc/datasmart/VASCO2025/C' . $nomar . '.txt';

            copy($origen, $destino);

            $rutaBat = "vistas/contabilidad/cancelaciones/CB$fi$ff.bat";
            $archivoBat = fopen($rutaBat, "w");

            $nombreEmpresa = "VASCO2025";

            fwrite($archivoBat, "MSISCONT.EXE " . $nombreEmpresa . " C" . $nomar . ".txt" . PHP_EOL);
            fclose($archivoBat);

            $origen2 = 'c:/xampp2/htdocs/vascorp/vistas/contabilidad/cancelaciones/CB' . $nomar . '.bat';
            #$destino2 = '//Sistemas-2/d/contabilidad/cancelaciones/CB'.$nomar.'.bat';
            $destino2 = '//Yudy-pc/datasmart/VASCO2025/CB' . $nomar . '.bat';
            copy($origen2, $destino2);

            $correlativo = ModeloContabilidad::mdlActualizarCorrelativo($añoI, $mesI, $corr04, "valor_3");
            $correlativo = ModeloContabilidad::mdlActualizarCorrelativo($añoI, $mesI, $corr08, "valor_4");
            #var_dump($correlativo);

            echo '<script>

            swal({
                type: "success",
                title: "Se genero el archivo correctamente",
                showConfirmButton: true,
                confirmButtonText: "Cerrar",
                closeOnConfirm: false
                }).then(function(result){
                    if (result.value) {

                    window.location = "procesar-ce";

                    }
                })

            </script>';
        }
    }

    static public function ctrGenerarClientesSiscont()
    {

        if (isset($_POST["inicioSiscontCli"])) {

            #var_dump($_POST["inicioSiscontCli"]);

            $fechaInicio = $_POST["inicioSiscontCli"];
            $fechaFin = $_POST["finSiscontCli"];

            $añoI = date("Y", strtotime($fechaInicio));
            $mesI = date("m", strtotime($fechaInicio));

            $fi = str_replace("-", "", $fechaInicio);
            $ff = str_replace("-", "", $fechaFin);

            $nomar = $fi . $ff;
            #var_dump($nomar);

            $ruta = "vistas/contabilidad/clientes/CL$fi$ff.txt";
            #var_dump($ruta);

            $archivo = fopen($ruta, "w");
            $clientes = ModeloContabilidad::mdlClientes($fechaInicio, $fechaFin);
            #var_dump($clientes);

            foreach ($clientes as $key => $value) {

                $rs = ControladorContabilidad::eliminar_tildes($value["rs"]);
                $ape1 = ControladorContabilidad::eliminar_tildes($value["ape1"]);
                $ape2 = ControladorContabilidad::eliminar_tildes($value["ape2"]);
                $nombre = ControladorContabilidad::eliminar_tildes($value["nombre"]);

                $nombre1 = explode(" ", $nombre);

                $origen     = str_pad(" ", 2);
                $voucher    = str_pad(" ", 5);
                $fecha      = str_pad(" ", 8);
                $cuenta     = str_pad(" ", 10);
                $debe       = str_pad(" ", 12, '0', STR_PAD_LEFT);
                $haber      = str_pad(" ", 12, '0', STR_PAD_LEFT);
                $moneda     = str_pad(" ", 1);
                $tc         = str_pad(" ", 10, '0', STR_PAD_LEFT);
                $doc        = str_pad(" ", 2);
                $numero     = str_pad(" ", 40);
                $fechad     = str_pad(" ", 8);
                $fechav     = str_pad(" ", 8);
                $codigo     = str_pad($value["ruc"], 15);
                $cc         = str_pad(" ", 10);
                $fe         = str_pad(" ", 4);
                $pre        = str_pad(" ", 10);
                $mpago      = str_pad(" ", 3);
                $glosa      = str_pad(" ", 60);
                $rnumero    = str_pad(" ", 40);
                $rtdoc      = str_pad(" ", 2);
                $rfecha     = str_pad(" ", 8);
                $snumero    = str_pad(" ", 40);
                $sfecha     = str_pad(" ", 8);
                $tl         = str_pad("V", 1);
                $neto       = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto2      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto3      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto4      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $igv        = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto5      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto6      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto7      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $neto8      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                #$neto9      = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $ruc        = str_pad($value["ruc"], 15);
                $tipo       = str_pad($value["tipo"], 1);
                $r5         = str_pad($rs, 60);
                $ape1       = str_pad($ape1, 20);
                $ape2       = str_pad($ape2, 20);
                $nombre     = str_pad($nombre1[0], 20);
                $tdoi       = str_pad($value["tdoci"], 1);
                $rnumdes    = str_pad(" ", 1);
                $rcodtasa   = str_pad(" ", 5);
                $rindret    = str_pad(" ", 1);
                $rmonto     = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $rigv       = str_pad("0.00", 12, '0', STR_PAD_LEFT);
                $tbien      = str_pad(" ", 1);

                fwrite($archivo,    $origen .
                    $voucher .
                    $fecha .
                    $cuenta .
                    $debe .
                    $haber .
                    $moneda .
                    $tc .
                    $doc .
                    $numero .
                    $fechad .
                    $fechav .
                    $codigo .
                    $cc .
                    $fe .
                    $pre .
                    $mpago .
                    $glosa .
                    $rnumero .
                    $rtdoc .
                    $rfecha .
                    $snumero .
                    $sfecha .
                    $tl .
                    $neto .
                    $neto2 .
                    $neto3 .
                    $neto4 .
                    $igv .
                    $neto5 .
                    $neto6 .
                    $neto7 .
                    $neto8 .
                    $ruc .
                    $tipo .
                    $r5 .
                    $ape1 .
                    $ape2 .
                    $nombre .
                    $tdoi .
                    $rnumdes .
                    $rcodtasa .
                    $rindret .
                    $rmonto .
                    $rigv .
                    $tbien .
                    PHP_EOL);
            }



            fclose($archivo);

            $origen = 'c:/xampp2/htdocs/vascorp/vistas/contabilidad/clientes/CL' . $nomar . '.txt';

            #$destino = '//Sistemas-2/d/contabilidad/clientes/V'.$nomar.'.txt';   
            $destino = '//Yudy-pc/datasmart/VASCO2025/CL' . $nomar . '.txt';

            copy($origen, $destino);

            $rutaBat = "vistas/contabilidad/clientes/CLB$fi$ff.bat";
            $archivoBat = fopen($rutaBat, "w");

            $nombreEmpresa = "VASCO2025";

            fwrite($archivoBat, "MSISCONT.EXE " . $nombreEmpresa . " CL" . $nomar . ".txt" . PHP_EOL);
            fclose($archivoBat);

            $origen2 = 'c:/xampp2/htdocs/vascorp/vistas/contabilidad/clientes/CLB' . $nomar . '.bat';
            #$destino2 = '//Sistemas-2/d/contabilidad/clientes/VB'.$nomar.'.bat';
            $destino2 = '//Yudy-pc/datasmart/VASCO2025/CLB' . $nomar . '.bat';
            copy($origen2, $destino2);

            echo '<script>

            swal({
                type: "success",
                title: "Se genero el archivo correctamente",
                showConfirmButton: true,
                confirmButtonText: "Cerrar",
                closeOnConfirm: false
                }).then(function(result){
                    if (result.value) {

                    window.location = "procesar-ce";

                    }
                })

            </script>';
        }
    }
}
