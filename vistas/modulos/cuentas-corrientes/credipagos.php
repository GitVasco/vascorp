<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            CrediPagos
            <small>Panel de CrediPagos</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">CrediPagos</li>
        </ol>
    </section>

    <section class="content">

        <div class="box">

            <div class="box-header with-border">
                <button class="btn btn-default" id="marcarTodos">
                    <i class="fa fa-check-square-o"></i> Seleccionar todos
                </button>
                <button class="btn btn-default" id="desmarcarTodos">
                    <i class="fa fa-square-o"></i> Deseleccionar todos
                </button>
                <button class="btn btn-danger" id="eliminarSeleccionados" disabled>
                    <i class="fa fa-trash"></i> Eliminar seleccionados
                </button>
                <span id="contadorSeleccionados" style="margin-left: 15px;">
                    Seleccionados: <strong>0</strong>
                </span>
            </div>

            <div class="box box-body">

                <div class="table-responsive">
                    <table class="table table-bordered table-striped dt-responsive tablaCredipagos" width="100%">
                        <thead>
                            <tr>
                                <th>Tip. Doc.</th>
                                <th>Num. Cta</th>
                                <th>Fec. Canc.</th>
                                <th>Cod. Cli.</th>
                                <th>Cliente</th>
                                <th>Monto</th>
                                <th>Vendedor</th>
                                <th>Notas</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                    </table>
                </div>
            </div>
        </div>

    </section>


</div>

<script>
    window.document.title = "Credipagos"
</script>