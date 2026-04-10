<div class="content-wrapper">

  <section class="content-header">
    
    <h1>
      
      Consultar cuentas
    
    </h1>

    <ol class="breadcrumb">
      
      <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
      
      <li class="active">Consultar cuentas</li>
    
    </ol>

  </section>

  <section class="content">

    <div class="box">

      <div class="box-header with-border">
  
        <div class="col-lg-2">
          <select name="tipoCliente" id="tipoCliente" class="form-control input-lg selectpicker" data-live-search="true">
          <option value="">Seleccionar cargar cliente</option></select>
        </div>

        <div class="col-lg-1">
          <button class="btn btn-primary" id="cargaClienteCuenta">Cargar Clientes</button>
        </div>


        <div class="col-lg-4 text-center bg-primary border-20">

        <span class="info-box-text">Cliente</span>
        <p class="info-box-number" name="consultaCliente" id="consultaCliente">-</p>
        <input type="hidden" id="CodCliBtn" name="CodCliBtn">

        </div>

        <div class="col-lg-2 text-center bg-green">

        <span class="info-box-text">Total Venta S/</span>
        <p class="info-box-number" name="consultaCredito" id="consultaCredito">0</p>

        </div>

        <div class="col-lg-1 text-center bg-yellow">

        <span class="info-box-text">Deuda Total S/</span>
        <p class="info-box-number" name="consultaDeudaTot" id="consultaDeudaTot">0</p>

        </div>   

        <div class="col-lg-1 text-center bg-red">

        <span class="info-box-text">Vencido  TotalS/</span>
        <p class="info-box-number" name="consultaDeudaVen" id="consultaDeudaVen">0</p>

        </div> 

        <div class="col-lg-1">
          <button class="btn btn-info" data-toggle="modal" data-target="#modalVerPagos" id="btnCargarPagos" >Pagos</button>
        </div>
 
      
    <div class="col-lg-12">
    </div>
        
      <div class="box-body">
        
       <table class="table table-bordered table-striped dt-responsive tablaCuentasConsultar" width="100%">
         
        <thead>
         
         <tr>
           <th>Tipo Doc.</th>
           <th>Nro Doc.</th>
           <th>Tipo</th>
           <th>Doc. origen</th>
           <th>Emisión</th>
           <th>Vencimiento</th>
           <th>Monto S/.</th>
           <th>Saldo S/.</th>
           <th>Fec. Pago</th>
           <th>Dif</th>
           <th>Protes.</th>
           <th>Renov.</th>
           <th>Bco.</th>
           <th>Nro. unico</th>
           <th>Vendedor</th>
           <th>Estado</th>
           <th>Acciones</th>

         </tr> 

        </thead>

        <tbody>

        </tbody>

       </table>

      </div>

    </div>

  </section>

</div>

<!--=====================================
MODAL AGREGAR USUARIO
======================================-->

<div id="modalVerPagos" class="modal fade" role="dialog">

  <div class="modal-dialog" style="max-width: 640px; width: calc(100% - 24px); margin: 12px auto;">

    <div class="modal-content">

      <div class="modal-header" style="background:#3c8dbc; color:white; padding:10px 15px;">

        <button type="button" class="close" data-dismiss="modal" style="margin-top:2px; opacity:0.9;">&times;</button>

        <h4 class="modal-title" style="font-size:16px;">Pagos · últimos 6 meses</h4>
        <p style="font-size:12px; opacity:0.92; margin:4px 0 0; font-weight:normal;">Montos por vendedor · S/.</p>

      </div>

      <div class="modal-body" style="padding:12px 15px;">

        <div class="table-responsive nuevosPagos-wrap" style="max-height:60vh; overflow-y:auto; border:1px solid #e8e8e8; border-radius:3px;">
          <div class="nuevosPagos"></div>
        </div>

      </div>

      <div class="modal-footer" style="padding:8px 15px;">

        <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cerrar</button>

      </div>

    </div>

  </div>

</div>


<script>
window.document.title = "Consultar cuentas"
</script>