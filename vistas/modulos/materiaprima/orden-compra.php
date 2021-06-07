<div class="content-wrapper">

  <section class="content-header">

    <h1>

      Orden de compra

    </h1>

    <ol class="breadcrumb">

      <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

      <li class="active">Orden de compra</li>

    </ol>

  </section>

  <section class="content">

    <div class="box">

      <div class="box-header with-border">

        <a href="crear-orden-compra">

          <button class="btn btn-primary">

            Agregar Orden de compra

          </button>

        </a>

        <button type="button" class="btn btn-default pull-right" id="daterange-btnOrdenCompra">
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

        <input type="hidden" value="<?=$_SESSION["perfil"];?>" id="perfilOculto">
        <input type="hidden" value="<?= $_GET["ruta"]; ?>" id="rutaAcceso">
       <table class="table table-bordered table-striped dt-responsive tablaOrdenesCompras" width="100%">
         
        <thead>
         
         <tr>
           
           <th>Est</th>
           <th>Serie</th>
           <th>Numero</th>
           <th>Proveedor</th>
           <th>Fec. Emisión</th> 
           <th>Estado</th>
           <th>Cerrar</th>
           <th style="width:120px">Acciones</th>

         </tr> 

        </thead>

       </table>

      </div>

    </div>

  </section>

</div>

<script>
window.document.title = "Orden de compra"
</script>