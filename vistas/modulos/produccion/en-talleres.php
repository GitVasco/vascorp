<?php
?>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            En Talleres
            <small>Producción</small>
        </h1>
        <ol class="breadcrumb
            <li><a href=" #"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">En Talleres</li>

        </ol>

    </section>

    <section class="content">

        <div class="box">
            <div class="box-header with-border">
                <div class="col-lg-2">
                    <select name="selectEnTalleres" id="selectEnTalleres" class="form-control input-lg selectpicker" data-live-search="true" data-size="10">
                        <option value="null">Seleccionar Taller</option>
                        <?php
                        $sectoresInternos = ControladorSectores::ctrSectoresPorTipo(0);
                        $sectoresExternos = ControladorSectores::ctrSectoresPorTipo(1);

                        echo '<optgroup label="Taller (interno)">';
                        foreach ($sectoresInternos as $value) {
                            echo '<option value="' . htmlspecialchars($value["cod_sector"]) . '">'
                                . htmlspecialchars($value["cod_sector"] . "-" . $value["nom_sector"])
                                . '</option>';
                        }
                        echo '</optgroup>';

                        echo '<optgroup label="Servicio (externo)">';
                        foreach ($sectoresExternos as $value) {
                            echo '<option value="' . htmlspecialchars($value["cod_sector"]) . '">'
                                . htmlspecialchars($value["cod_sector"] . "-" . $value["nom_sector"])
                                . '</option>';
                        }
                        echo '</optgroup>';
                        ?>
                    </select>
                </div>

                <a href="vistas/reportes_excel/rpt_entalleres_grupal.php" class="btn btn-default pull-right" style="border:green 1px solid">
                    <img src="vistas/img/plantilla/excel.png" width="20px"> En Talleres Grupal
                </a>
                <a href="vistas/reportes_excel/rpt_entalleres.php" class="btn btn-default pull-right" style="border:green 1px solid">
                    <img src="vistas/img/plantilla/excel.png" width="20px"> En Talleres
                </a>
            </div>
            <div class="box-body">

                <input type="hidden" value="<?= $_SESSION["perfil"]; ?>" id="perfilOculto">

                <table class="table table-bordered table-striped dt-responsive tablaEnTalleres" width="100%">

                    <thead>

                        <tr>
                            <th>Fecha</th>
                            <th>Guia</th>
                            <th>Taller</th>
                            <th>Modelo</th>
                            <th>Nombre</th>
                            <th>Color</th>
                            <th>Talla</th>
                            <th>Cantidad</th>
                            <th>Saldo</th>
                            <th>Acciones</th>
                        </tr>

                    </thead>

                </table>

            </div>

        </div>

    </section>

</div>


<script>
    window.document.title = "En Talleres"
</script>