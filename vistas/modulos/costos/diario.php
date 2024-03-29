<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Diario

        </h1>

        <ol class="breadcrumb">

            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Diario</li>

        </ol>

    </section>

    <section class="content">

        <div class="box">

            <div class="box-header">

                <div class="col-lg-6 text-center">

                    <button class="btn btn-default  btnEneD" id="btnEneD" name="btnEneD" value="1">
                        Ene
                    </button>
                    <button class="btn btn-default  btnFebD" id="btnFebD" name="btnFebD" value="2">
                        Feb
                    </button>
                    <button class="btn btn-default  btnMarD" id="btnMarD" name="btnMarD" value="3">
                        Mar
                    </button>
                    <button class="btn btn-default  btnAbrD" id="btnAbrD" name="btnAbrD" value="4">
                        Abr
                    </button>
                    <button class="btn btn-default  btnMayD" id="btnMayD" name="btnMayD" value="5">
                        May
                    </button>
                    <button class="btn btn-default  btnJunD" id="btnJunD" name="btnJunD" value="6">
                        Jun
                    </button>
                    <button class="btn btn-default  btnJulD" id="btnJulD" name="btnJulD" value="7">
                        Jul
                    </button>
                    <button class="btn btn-default  btnAgoD" id="btnAgoD" name="btnAgoD" value="8">
                        Ago
                    </button>
                    <button class="btn btn-default  btnSepD" id="btnSepD" name="btnSepD" value="9">
                        Sep
                    </button>
                    <button class="btn btn-default  btnOctD" id="btnOctD" name="btnOctD" value="10">
                        Oct
                    </button>
                    <button class="btn btn-default  btnNovD" id="btnNovD" name="btnNovD" value="11">
                        Nov
                    </button>
                    <button class="btn btn-default  btnDicD" id="btnDicD" name="btnDicD" value="12">
                        Dic
                    </button>
                    
                </div>



                <div class="col-lg-5 text-center">

                    <form role="form"  method="POST" enctype="multipart/form-data">
                        <div class="col-lg-6">
                            <input type="file" name="archivoxlsventa" id="archivoxlsventa" class="form-control" accept="application/vnd.ms-excel">
                        </div>
                        <div class="col-lg-6">
                            <button type="submit"  class="btn btn-success" name="importventa" ><i class="fa fa-refresh"></i> Cargar Diario</a>
                        </div>
                    </form>

                </div>

                <div class="col-lg-1 text-center">
                    <button type="button" class="btn btn-danger" id="borrarMesD" name="borrarMesD" data-toggle="modal" data-target="#modalBorrarMesD">Borrar Mes
                    </button>
                </div>

            </div>

            <div class="box-body">

                <div class="box-header with-border">
                    Mostrar/Ocultar:  
                    <!-- <a class="toggle-vis" data-column="0">Cta</a> - --> 
                    <!-- <a class="toggle-vis" data-column="1">O.</a> - --> 
                    <!-- <a class="toggle-vis" data-column="0">Cta</a> - --> 
                    <!-- <a class="toggle-vis" data-column="2">Voucher</a> - --> 
                    <!-- <a class="toggle-vis" data-column="3">Cuenta</a> - --> 
                    <!-- <a class="toggle-vis" data-column="4">Descripción</a> - --> 
                    <!-- <a class="toggle-vis" data-column="5">Débito</a> - -->
                    <!-- <a class="toggle-vis" data-column="6">Crédito</a> - -->
                    <a class="toggle-vis" data-column="8">M</a> -
                    <a class="toggle-vis" data-column="9">T/C</a> -
                    <!-- <a class="toggle-vis" data-column="9">Fecha</a> - -->
                    <a class="toggle-vis" data-column="11">Concepto</a> -
                    <!-- <a class="toggle-vis" data-column="11">Ruc</a> - -->
                    <!-- <a class="toggle-vis" data-column="12">Razón Social</a> - -->
                    <!-- <a class="toggle-vis" data-column="13">TD</a> - -->
                    <!-- <a class="toggle-vis" data-column="14">Documento</a> - -->
                    <!-- <a class="toggle-vis" data-column="15">F. Emi</a> - -->
                    <!-- <a class="toggle-vis" data-column="16">F. Ven</a> - -->
                    <!-- <a class="toggle-vis" data-column="17">Suc</a> - -->
                    <!-- <a class="toggle-vis" data-column="18">CCC</a> -->
                </div>

                <table class="table table-bordered table-striped dt-responsive TablaDiario" width="100%">

                    <thead>

                        <tr>

                            <th>Cta</th>
                            <th>Cód</th>
                            <th>O.</th>
                            <th>V.</th>
                            <th>Cuenta</th>
                            <th>Descripción</th>
                            <th>Debito</th>
                            <th>Credito</th>
                            <th>M</th>
                            <th>T/C</th>
                            <th width="60px">Fecha</th>
                            <th width="300px">Concepto</th>
                            <th>Ruc</th>
                            <th width="300px">Razón Social</th>
                            <th>TD</th>
                            <th>Documento</th>
                            <th width="60px">F. Emi</th>
                            <th width="60px">F. Ven</th>
                            <th>Suc.</th>
                            <th>#</th>

                        </tr>

                    </thead>

                    <tbody>
                    </tbody>

                </table>

            </div>

        </div>

    </section>

</div>

<script>
    window.document.title = "Diario";
</script>