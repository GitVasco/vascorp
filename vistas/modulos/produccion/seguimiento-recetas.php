<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Seguimiento recetas

        </h1>

        <ol class="breadcrumb">

            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Producción</li>

        </ol>

    </section>

    <section class="content seg-recetas-content">

        <div id="segRecetasOverlay" class="seg-recetas-overlay" aria-hidden="true">
            <div class="seg-recetas-overlay__panel">
                <i class="fa fa-spinner fa-spin fa-2x"></i>
                <p id="segRecetasOverlayMsg">Cargando…</p>
            </div>
        </div>

        <div class="box">

            <div class="box-header with-border">

                <div class="col-lg-2">
                    <select name="selSegRecetaLinea" id="selSegRecetaLinea" class="form-control input-lg selectpicker" data-live-search="true" data-size="10">
                        <option value="">-------- Línea -------</option>
                    </select>
                </div>

                <div class="col-lg-3">
                    <select name="selSegRecetaSublinea" id="selSegRecetaSublinea" class="form-control input-lg selectpicker" data-live-search="true" data-size="10">
                        <option value="">-------- Sublínea -------</option>
                    </select>
                </div>

                <div class="col-lg-3">
                    <select name="selSegRecetaMp" id="selSegRecetaMp" class="form-control input-lg selectpicker" data-live-search="true" data-size="10">
                        <option value="">-------- Materia prima -------</option>
                    </select>
                </div>

                <div class="col-lg-2">
                    <button type="button" class="btn btn-primary btnBuscarSeguimientoRecetas"><i class="fa fa-search"></i> Buscar</button>
                    <button type="button" class="btn btn-default btnLimpiarSeguimientoRecetas"><i class="fa fa-refresh"></i> Limpiar</button>
                </div>

                <a href="#" id="btnExportSeguimientoRecetas" class="btn btn-default pull-right" style="border:green 1px solid">
                    <img src="vistas/img/plantilla/excel.png" width="20px"> Exportar
                </a>

            </div>

            <div class="box-body">

                <input type="hidden" value="<?= $_SESSION["perfil"]; ?>" id="perfilOculto">

                <table class="table table-bordered table-striped dt-responsive tablaSeguimientoRecetas" width="100%">

                    <thead>

                        <tr>

                            <th>Modelo</th>
                            <th>Nombre</th>
                            <th>Color</th>
                            <th>Talla</th>
                            <th>Estado</th>
                            <th>Proyección</th>
                            <th>% Avance</th>
                            <th>Stock</th>
                            <th>Pedidos</th>
                            <th>En Taller</th>
                            <th>En Servicio</th>
                            <th>En Arreglos</th>
                            <th>Alm. Corte</th>
                            <th>Ord. Corte</th>
                            <th>Ult 30d</th>
                            <th>Duración Mes</th>

                        </tr>

                    </thead>

                    <tbody>


                    </tbody>

                </table>

            </div>

        </div>

        <div class="box box-info" id="boxExplosionMpOrdCorte" style="display:none;">

            <div class="box-header with-border">
                <h3 class="box-title">Materia prima requerida (según ord. corte)</h3>
                <span class="label label-default pull-right" id="lblExplosionMpResumen"></span>
            </div>

            <div class="box-body">

                <div id="alertExplosionMpErrores" class="alert alert-warning" style="display:none;"></div>

                <table class="table table-bordered table-striped tablaExplosionMpOrdCorte" width="100%">

                    <thead>
                        <tr>
                            <th>MP</th>
                            <th>Descripción</th>
                            <th>Color</th>
                            <th>Unidad</th>
                            <th>Cantidad necesaria</th>
                            <th>Rol</th>
                        </tr>
                    </thead>

                    <tbody></tbody>

                </table>

            </div>

        </div>

    </section>

</div>

<script>
    window.document.title = "Seguimiento recetas"
</script>

<style>
    .seg-recetas-content {
        position: relative;
    }

    .seg-recetas-overlay {
        display: none;
        position: absolute;
        inset: 0;
        z-index: 20;
        background: rgba(255, 255, 255, 0.72);
        align-items: center;
        justify-content: center;
    }

    .seg-recetas-overlay.is-on {
        display: flex;
    }

    .seg-recetas-overlay__panel {
        text-align: center;
        color: #3c8dbc;
        font-size: 15px;
        font-weight: 600;
    }

    .seg-recetas-overlay__panel p {
        margin: 12px 0 0;
    }
</style>
