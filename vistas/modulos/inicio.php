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


            echo '<div class="box box-success">

                            <div class="box-header">

                                <h1>Bienvenid@ ' . $_SESSION["nombre"] . '</h1>

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

                            // Generar opciones para los últimos 12 meses
                            for ($i = 0; $i < 12; $i++) {
                                $mesNumero = $mesActual - $i;
                                $año = $añoActual;

                                if ($mesNumero <= 0) {
                                    $mesNumero += 12;
                                    $año--;
                                }

                                $nombreMes = $meses[$mesNumero];
                                $label = $i == 0 ? 'Mes Actual' : $nombreMes . ' ' . $año;
                                $value = $i == 0 ? 'null' : $mesNumero;
                                $añoData = $i == 0 ? '' : $año;
                                $selected = $i == 0 ? 'selected' : '';

                                echo '<option value="' . $value . '" data-año="' . $añoData . '" ' . $selected . '>' . $label . '</option>';
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

                include "reportes/movimiento_modelo.php";

                ?>

            </div>


        </div>

        <div class="row">

            <div class="col-lg-6">

                <?php

                include "reportes/corte-prod.php";

                ?>

            </div>

            <div class="col-lg-6">

                <?php

                include "reportes/prod-taller.php";

                ?>

            </div>


        </div>


    </section>

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


</div>

<script>
    window.document.title = "Inicio"

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
            actualizarCajas(mesSeleccionado, añoSeleccionado);
        });
    });
</script>