<?php

// $valor = null;
// $respuesta = ControladorMantenimiento::ctrTraerCalendario($valor);

?>


<div class="content-wrapper">

    <section class="content-header">
        <h1>
            Dashboard Mes Actual

            <small>Página de control</small>

        </h1>

        <ol class="breadcrumb">

            <li><a href="#"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Dashboard</li>

        </ol>

    </section>

    <section class="content">

        <div class="col-lg-12">

            <?php


            $mesActual = date('n');
            $añoActual = date('Y');
            $meses = array(
                1 => 'Enero',
                2 => 'Febrero',
                3 => 'Marzo',
                4 => 'Abril',
                5 => 'Mayo',
                6 => 'Junio',
                7 => 'Julio',
                8 => 'Agosto',
                9 => 'Septiembre',
                10 => 'Octubre',
                11 => 'Noviembre',
                12 => 'Diciembre'
            );
            $periodoActual = $meses[$mesActual] . ' ' . $añoActual;

            echo '<div class="box box-success">

                            <div class="box-header">

                                <h1>Bienvenid@ ' . $_SESSION["nombre"] . ' - <span id="periodo-actual" style="font-size: 0.8em; color: #666; font-weight: normal;">Período: <strong>' . $periodoActual . '</strong></span></h1>

                            </div>

                         </div>';


            ?>

        </div>

        <div class="row" id="cajas-superiores-container">

            <div class="col-lg-2 col-xs-6">

                <div class="small-box bg-blue">

                    <div class="inner" style="position: relative; z-index: 10;">

                        <h3 style="font-size: 20px; margin-bottom: 10px;">Mes</h3>

                        <select class="form-control" id="selectMes" name="selectMes" style="background-color: white; color: #333; font-weight: bold; position: relative; z-index: 1000;">

                            <option value="null" data-año="">Mes Actual</option>

                            <?php

                            $meses = array(
                                1 => 'Enero',
                                2 => 'Febrero',
                                3 => 'Marzo',
                                4 => 'Abril',
                                5 => 'Mayo',
                                6 => 'Junio',
                                7 => 'Julio',
                                8 => 'Agosto',
                                9 => 'Septiembre',
                                10 => 'Octubre',
                                11 => 'Noviembre',
                                12 => 'Diciembre'
                            );

                            $mesActual = date('n');
                            $añoActual = date('Y');
                            $añoInicio = 2025;

                            // Array para almacenar todas las opciones
                            $opciones = array();

                            // Agregar opción "Mes Actual"
                            $opciones[] = array(
                                'value' => 'null',
                                'año' => '',
                                'label' => 'Mes Actual',
                                'timestamp' => $añoActual * 100 + $mesActual, // Para ordenar
                                'selected' => true
                            );

                            // Generar opciones para los últimos 12 meses hacia atrás
                            for ($i = 1; $i < 12; $i++) {
                                $mesNumero = $mesActual - $i;
                                $año = $añoActual;

                                if ($mesNumero <= 0) {
                                    $mesNumero += 12;
                                    $año--;
                                }

                                $nombreMes = $meses[$mesNumero];
                                $opciones[] = array(
                                    'value' => $mesNumero,
                                    'año' => $año,
                                    'label' => $nombreMes . ' ' . $año,
                                    'timestamp' => $año * 100 + $mesNumero,
                                    'selected' => false
                                );
                            }

                            // Generar opciones para meses pasados desde enero 2025 hasta el mes actual (incluido)
                            for ($año = $añoInicio; $año <= $añoActual; $año++) {
                                $mesInicio = 1;

                                if ($año < $añoActual) {
                                    $mesFin = 12;
                                } else {
                                    $mesFin = $mesActual;
                                }

                                for ($mesNumero = $mesInicio; $mesNumero <= $mesFin; $mesNumero++) {
                                    // Verificar si este mes ya está en la lista de los últimos 12 meses
                                    $yaIncluido = false;
                                    if (!($mesNumero == $mesActual && $año == $añoActual)) {
                                        for ($j = 1; $j < 12; $j++) {
                                            $mesCheck = $mesActual - $j;
                                            $añoCheck = $añoActual;
                                            if ($mesCheck <= 0) {
                                                $mesCheck += 12;
                                                $añoCheck--;
                                            }
                                            if ($mesNumero == $mesCheck && $año == $añoCheck) {
                                                $yaIncluido = true;
                                                break;
                                            }
                                        }
                                    }

                                    if (!$yaIncluido) {
                                        $nombreMes = $meses[$mesNumero];
                                        $opciones[] = array(
                                            'value' => $mesNumero,
                                            'año' => $año,
                                            'label' => $nombreMes . ' ' . $año,
                                            'timestamp' => $año * 100 + $mesNumero,
                                            'selected' => false
                                        );
                                    }
                                }
                            }

                            // Ordenar opciones de mayor a menor (más reciente a más antiguo)
                            // Mantener "Mes Actual" al principio
                            $mesActualOption = array_shift($opciones);
                            usort($opciones, function ($a, $b) {
                                return $b['timestamp'] - $a['timestamp'];
                            });
                            array_unshift($opciones, $mesActualOption);

                            // Mostrar las opciones ordenadas
                            foreach ($opciones as $opcion) {
                                $selected = $opcion['selected'] ? 'selected' : '';
                                echo '<option value="' . $opcion['value'] . '" data-año="' . $opcion['año'] . '" ' . $selected . '>' . $opcion['label'] . '</option>';
                            }

                            ?>

                        </select>

                    </div>

                    <div class="icon" style="z-index: 1; opacity: 0.3;">

                        <i class="fa fa-calendar"></i>

                    </div>

                    <a href="#" class="small-box-footer" style="cursor: default;">

                        Seleccionar <i class="fa fa-arrow-circle-right"></i>

                    </a>

                </div>

            </div>

            <?php

            include "inicio/cajas-superiores.php";

            ?>

        </div>

        <div class="row">

            <div class="col-lg-6">

                <?php

                include "reportes/vtas-prod.php";

                ?>

            </div>

            <div class="col-lg-6">

                <?php

                include "reportes/corte-prod.php";

                ?>

            </div>

        </div>

        <div class="row">

            <div class="col-lg-12">

                <?php

                include "reportes/prod-taller.php";

                ?>

            </div>

            <!-- Gráfico de Movimiento por Modelo - COMENTADO -->
            <!--
            <div class="col-lg-6">

                <?php

                include "reportes/movimiento_modelo.php";

                ?>

            </div>
            -->

        </div>

        <div class="row">

            <div class="col-lg-12">

                <?php

                include "inicio/flujo-corte.php";

                ?>

            </div>

        </div>


    </section>

    <!-- Sección Dashboard Mes Pasado - COMENTADA para que no se cargue -->
    <!--
    <section class="content-header">

        <h1>
            Dashboard Mes Pasado
        </h1>

    </section>

    <section class="content">

        <div class="row">

            <?php

            include "inicio/cajas-inferiores.php";

            ?>

        </div>

        <div class="row">

            <div class="col-lg-6">

                <?php

                include "reportes/vtas-modP.php";

                ?>

            </div>

            <div class="col-lg-6">

                <?php

                include "reportes/modelos_vdos.php";

                ?>

            </div>


        </div>

    </section>
    -->


</div>

<script>
    window.document.title = "Inicio"

    // Función para actualizar el período en el saludo
    function actualizarPeriodo(mes, año) {
        var meses = {
            1: 'Enero',
            2: 'Febrero',
            3: 'Marzo',
            4: 'Abril',
            5: 'Mayo',
            6: 'Junio',
            7: 'Julio',
            8: 'Agosto',
            9: 'Septiembre',
            10: 'Octubre',
            11: 'Noviembre',
            12: 'Diciembre'
        };

        var periodoTexto;
        if (mes === 'null' || mes === null) {
            // Mes actual
            var fechaActual = new Date();
            var mesActual = fechaActual.getMonth() + 1;
            var añoActual = fechaActual.getFullYear();
            periodoTexto = meses[mesActual] + ' ' + añoActual;
        } else {
            periodoTexto = meses[parseInt(mes)] + ' ' + año;
        }

        $("#periodo-actual").html('Período: <strong>' + periodoTexto + '</strong>');
    }

    // Función para actualizar las cajas según el mes seleccionado
    function actualizarCajas(mes, año) {
        $.ajax({
            url: "ajax/inicio.ajax.php",
            method: "POST",
            data: {
                mes: mes,
                año: año
            },
            dataType: "json",
            success: function(respuesta) {
                // Actualizar caja de ventas
                $("#cajas-superiores-container .bg-aqua .inner h3").text(respuesta.ventas + " und");

                // Actualizar caja de producción
                $("#cajas-superiores-container .bg-green .inner h3").text(respuesta.produccion + " und");

                // Actualizar caja de cortes
                $("#cajas-superiores-container .bg-purple .inner h3").text(respuesta.cortes + " und");

                // Actualizar caja de pedidos
                $("#cajas-superiores-container .bg-yellow .inner h3").text(respuesta.pedidos);

                // Actualizar caja de faltantes
                $("#cajas-superiores-container .bg-red .inner h3").text(respuesta.faltantes);
                $("#cajas-superiores-container .bg-red .inner p").text("Unidades faltantes: " + respuesta.porcentaje + " %");
            },
            error: function(xhr, status, error) {
                console.error("Error al actualizar las cajas:", error);
            }
        });
    }

    // Event listener para el cambio de mes
    $(document).ready(function() {
        $("#selectMes").on("change", function() {
            var mesSeleccionado = $(this).val();
            var añoSeleccionado = $(this).find("option:selected").data("año");

            // Actualizar el período en el saludo
            actualizarPeriodo(mesSeleccionado, añoSeleccionado);

            // Actualizar las cajas
            actualizarCajas(mesSeleccionado, añoSeleccionado);
        });
    });
</script>