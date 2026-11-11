<div class="content-wrapper">

  <section class="content-header">

    <h1>

    Cierres

    </h1>

    <ol class="breadcrumb">

      <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

      <li class="active">Cierres</li>

    </ol>

  </section>

  <section class="content">

    <div class="box">
      <div class="box-header with-border">
        <a href="crear-cierre">
          <button class="btn btn-primary btnRegistroCierre" name="RegistrarCierre" >
            Registrar Cierre
          </button>
        </a>
        <a href="crear-cierre-manual" class="btn btn-warning"><i class="fa fa-lock"></i> Cierre manual</a>
        <button class="btn btn-info btnCierreDeta" data-toggle='modal' data-target='#modalVerCierreDeta' codigoServicio><i class="fa fa-eye"></i> Ver cierres</button>
        <button class="btn btn-outline-success btnReporteCierre"  style="border:green 1px solid">
                      <img src="vistas/img/plantilla/excel.png" width="20px"> Reporte Cierres  </button>

        <button type="button" class="btn btn-default pull-right" id="daterange-btnCierres">
          <span>
            <i class="fa fa-calendar"></i>

            <?php

              if(isset($_GET["fechaInicial"])){

                echo $_GET["fechaInicial"]." - ".$_GET["fechaFinal"];

              }else{
              
                echo 'Rango de fecha';

              }

            ?>

          </span>

          <i class="fa fa-caret-down"></i>

        </button>
      </div>
      <div class="box-body">

        <input type="hidden" value="<?= $_SESSION["perfil"]; ?>" id="perfilOculto">
        <input type="hidden" value="<?= $_GET["ruta"]; ?>" id="rutaAcceso">

        <div class="nav-tabs-custom">
          <ul class="nav nav-tabs">
            <li class="active"><a href="#tabCierres" data-toggle="tab"><i class="fa fa-list"></i> Cierres</a></li>
            <li><a href="#tabCierresManuales" data-toggle="tab"><i class="fa fa-lock"></i> Cierres manuales</a></li>
          </ul>

          <div class="tab-content">

            <div class="tab-pane active" id="tabCierres">

              <table class="table table-bordered table-striped dt-responsive tablaCierres" width="100%">

                <thead>

                  <tr>

                    <th>#</th>
                    <th>Codigo</th>
                    <th>N° Guia</th>
                    <th>Usuario</th>
                    <th>Taller</th>
                    <th>Total</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Estado Pago</th>
                    <th>Acciones</th>

                  </tr>

                </thead>

              </table>

            </div>

            <div class="tab-pane" id="tabCierresManuales">

              <div class="form-inline" style="margin-bottom:10px">
                <label>Desde</label>
                <input type="date" id="cmDesde" class="form-control input-sm" value="<?= date('Y-m-01'); ?>">
                <label>Hasta</label>
                <input type="date" id="cmHasta" class="form-control input-sm" value="<?= date('Y-m-d'); ?>">
                <button type="button" class="btn btn-sm btn-primary" id="cmBtnFiltrar"><i class="fa fa-search"></i> Filtrar</button>
              </div>

              <table class="table table-bordered table-striped tablaCierresManuales" width="100%">
                <thead>
                  <tr>
                    <th>Código</th>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Taller</th>
                    <th>Tipo de falla</th>
                    <th>Observación</th>
                    <th>Líneas</th>
                    <th>Total</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
              </table>

            </div>

          </div>
        </div>

      </div>

    </div>

  </section>

</div>


<!--=====================================
MODAL VISUALIZAR INFORMACION
======================================-->

<div id="modalVisualizarCierre" class="modal fade" role="dialog">
  
  <div class="modal-dialog" style="width: 70% !important;">

    <div class="modal-content">

      <form role="form" method="post">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Detalle del Cierre</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="box-body">

            <!-- ENTRADA PARA CODIGO DEL OC-->
            
            <div class="form-group col-lg-2">
              
              <label>Cierre</label>

              <div class="input-group">

              <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span> 

                <strong><input type="text" class="form-control input-sm" name="cierre" id="cierre" required readonly></strong>

              </div>

            </div>

            <!-- ENTRADA PARA LA GUIA-->
            
            <div class="form-group col-lg-2">

              <label>N° Guia</label>
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span> 

                <strong><input type="text" class="form-control input-sm" name="guia" id="guia" required readonly></strong>
                <input type="hidden" name="idCierre" id="idCierre">
              </div>

            </div> 

            <!-- ENTRADA PARA LA FECHA-->
            
            <div class="form-group col-lg-2">

              <label>Creación</label>
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span> 

                <strong><input type="datetime" class="form-control input-sm" name="fecha" id="fecha" data-inputmask="'mask':'9999-99-99 99:99:99'" data-mask required ></strong>

              </div>

            </div>   
 
            <!-- ENTRADA PARA LA RESPONSABLE-->
            
            <div class="form-group col-lg-2">

              <label>Responsable</label>
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span> 

                <strong><input type="text" class="form-control input-sm" name="nombre" id="nombre" required readonly></strong>

              </div>

            </div>            
   
            
            <!-- ENTRADA PARA LA CANTIDAD-->
            
            <div class="form-group col-lg-2">

              <label>Cantidad Total</label>
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span> 

                <strong><input type="text" class="form-control input-sm" name="cantidad" id="cantidad" required readonly></strong>

              </div>

            </div>
            
            <!-- ENTRADA PARA EL ESTADO-->
            
            <div class="form-group col-lg-2">

              <label>Estado</label>
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span> 

                <strong><input type="text" class="form-control input-sm" name="estado" id="estado" required readonly></strong>

              </div>

            </div>
                       
            <!-- TABLA DE DETALLES -->

            <div class="form-group col-lg-12">
            <label>TABLA DETALLES</label>
            </div>

            <div class="box-body">

              <table class="table table-bordered table-striped dt-responsive tablaDetalleOC" width="100%">

              <thead>

                <tr>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th>S</th>
                  <th>M</th>
                  <th>L</th>
                  <th>XL</th>
                  <th>XXL</th>
                  <th>XS</th>
                  <th></th>
                  <th></th>
                  <th></th>
                </tr>

                <tr>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th>28</th>
                  <th>30</th>
                  <th>32</th>
                  <th>34</th>
                  <th>36</th>
                  <th>38</th>
                  <th>40</th>
                  <th>42</th>
                  <th></th>
                </tr>

                <tr>
                  <th>Taller</th>
                  <th>Guia</th>
                  <th>Fecha</th>
                  <th>Codigo</th>
                  <th>Modelo</th>
                  <th>Nombre</th>
                  <th>Color</th>
                  <th>3</th>
                  <th>4</th>
                  <th>6</th>
                  <th>8</th>
                  <th>10</th>
                  <th>12</th>
                  <th>14</th>
                  <th>16</th>
                  <th>Total</th>
                </tr>

                </thead>

                <tbody>



                </tbody>

              </table>

            </div>

          </div>

        </div>

        <!--=====================================
        PIE DEL MODAL
        ======================================-->

        <div class="modal-footer">

          <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Salir</button>
          <button type="submit" class="btn btn-success pull-right" ><i class="fa fa-calendar"></i> Cambiar fecha</button>
        </div>



      </form>
      <?php

        $fechaCierre = new ControladorCierres();
        $fechaCierre -> ctrCambiarFechaCierre();

        ?>      
    </div>

  </div>

</div>


<!--=====================================
MODAL VISUALIZAR INFORMACION
======================================-->

<div id="modalVerCierreDeta" class="modal fade" role="dialog">
  
  <div class="modal-dialog" style="width: 65% !important;">

    <div class="modal-content">

      <form role="form" method="post">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Detalle del Cierre - GRAL</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->
        
        <div class="modal-body">
          <div class="box-header width-border">
            <button type="button" class="btn btn-default pull-right" id="daterange-btnVerCierres">
            <span>
              <i class="fa fa-calendar"></i>

              <?php

                if(isset($_GET["fechaInicial"])){

                  echo $_GET["fechaInicial"]." - ".$_GET["fechaFinal"];

                }else{
                
                  echo 'Rango de fecha';

                }

              ?>

            </span>

            <i class="fa fa-caret-down"></i>

          </button>
          
          </div>
          
          <div class="box-body">
            <table class="table table-bordered table-striped  tablaDetalleCierrreTotal" width="100%">

              <thead>

                <tr>
                  <th style="width:100px"></th>
                  <th></th>
                  <th style="width:130px"></th>
                  <th></th>
                  <th></th>
                  <th style="width:180px"></th>
                  <th style="width:100px"></th>
                  <th>S</th>
                  <th>M</th>
                  <th>L</th>
                  <th>XL</th>
                  <th>XXL</th>
                  <th>XS</th>
                  <th></th>
                  <th></th>
                  <th></th>
                </tr>

                <tr>
                  <th style="width:100px"></th>
                  <th></th>
                  <th style="width:130px"></th>
                  <th></th>
                  <th></th>
                  <th style="width:180px"></th>
                  <th style="width:100px"></th>
                  <th>28</th>
                  <th>30</th>
                  <th>32</th>
                  <th>34</th>
                  <th>36</th>
                  <th>38</th>
                  <th>40</th>
                  <th>42</th>
                  <th></th>
                </tr>

                <tr>
                  <th style="width:100px">Taller</th>
                  <th>Guia</th>
                  <th style="width:130px">Fecha</th>
                  <th>Codigo</th>
                  <th>Modelo</th>
                  <th style="width:180px">Nombre</th>
                  <th style="width:100px">Color</th>
                  <th>3</th>
                  <th>4</th>
                  <th>6</th>
                  <th>8</th>
                  <th>10</th>
                  <th>12</th>
                  <th>14</th>
                  <th>16</th>
                  <th>Total</th>
                </tr>

                </thead>

                <tbody>



                </tbody>

              </table>
          </div>

              



        </div>

        <!--=====================================
        PIE DEL MODAL
        ======================================-->

        <div class="modal-footer">

          <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Salir</button>

        </div>



      </form>

    </div>

  </div>

</div>
<!--=====================================
MODAL DETALLE DE CIERRE MANUAL
======================================-->
<div id="modalDetalleCierreManual" class="modal fade" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:#f39c12; color:white">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Cierre manual <span id="cmDetTitulo"></span></h4>
      </div>
      <div class="modal-body">
        <p id="cmDetCabecera" class="text-muted"></p>
        <table class="table table-bordered table-striped table-condensed" id="cmDetTabla">
          <thead>
            <tr>
              <th>Servicio</th>
              <th>Guía</th>
              <th>Modelo</th>
              <th>Color</th>
              <th>Talla</th>
              <th class="text-right">Saldo antes</th>
              <th class="text-right">Cerrado</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Salir</button>
      </div>
    </div>
  </div>
</div>

<script>
window.document.title = "Cierres"
</script>