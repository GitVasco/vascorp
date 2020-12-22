<div class="content-wrapper">

  <section class="content-header">
    
    <h1>
    <?php
                $cuentas=ControladorCuentas::ctrMostrarCuentas("id",$_GET["idCuenta"]);

     ?>
      Administrar cancelaciones de N° de cuenta <?php echo $cuentas["num_cta"]?>
    
    </h1>

    <ol class="breadcrumb">
      
      <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
      
      <li class="active">Administrar cancelaciones</li>
    
    </ol>

  </section>

  <section class="content">

    <div class="box">

        
      <div class="box-body">
        
       <table class="table table-bordered table-striped dt-responsive tablas">
         
        <thead>
         
         <tr>
           <th>Nro Doc.</th>
           <th>Vendedor</th>
           <th>Monto</th>
           <th>Notas</th>
           <th>Acciones</th>

         </tr> 

        </thead>

        <tbody>
            <?php
                $cancelaciones=ControladorCuentas::ctrMostrarCancelaciones("id",$_GET["idCuenta"]);
                foreach ($cancelaciones as $key => $value) {
           
                    echo    ' <tr>
        
                                <td>'.$value["num_cta"].'</td>
            
                                <td>'.$value["vendedor"].'</td>
                                
                                <td>'.$value["monto"].'</td>
                                
                                <td>'.$value["notas"].'</td>

                                <td>

                                    <div class="btn-group">
                                        
                                        <button class="btn btn-warning btnEditarCancelacion" idCancelacion="'.$value["id"].'" data-toggle="modal" data-target="#modalEditarCancelacion"><i class="fa fa-pencil"></i></button><button class="btn btn-danger btnEliminarCancelacion" idCancelacion="'.$value["id"].'" ><i class="fa fa-times"></i></button>
                                    
                                    </div>  

                                 </td>
                                
                            </tr>';
                }
            ?>
        </tbody>

       </table>

      </div>

    </div>

  </section>

</div>


<!--=====================================
MODAL EDITAR TIPO PAGO
======================================-->

<div id="modalEditarCancelacion" class="modal fade" role="dialog">
  
  <div class="modal-dialog" style="width: 85% !important;">

    <div class="modal-content">

      <form role="form" method="post">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Editar Cancelacion</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="box-body">

          
            <!-- ENTRADA PARA EL CODIGO -->
            
            <div class="form-group col-lg-2">
            <label for=""><b>Documento por cancelar</b></label>
            <label for=""><b>Tipo de vendedor</b></label>
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-key"></i></span> 

                <select type="text" class="form-control input-lg selectpicker" name="cancelarVendedor" id="cancelarVendedor" data-live-search="true"  required>
                  <option value="">Seleccionar vendedor</option>

                    <?php
                      $item= null;
                      $valor = null;

                    $documentos = ControladorVendedores::ctrMostrarVendedores($item,$valor);

                    foreach ($documentos as $key => $value) {
                      echo '<option value="' . $value["codigo"] . '">' .$value["codigo"]. " - " . $value["descripcion"] . '</option>';
                    }

                    ?>   
                 </select>    
                <input type="hidden" id="cancelarUsuario" name="cancelarUsuario" value="<?php echo $_SESSION["id"]?>">
                <input type="hidden" id="idCuenta2" name="idCuenta2" >
              </div>

            </div>          

            <!-- ENTRADA PARA EL NOMBRE -->
            
            <div class="form-group col-lg-2">
              <div style="margin-top:23px"></div>
              <label for=""><b>Nro de documento</b></label>
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-credit-card"></i></span> 

                <input type="text" class="form-control input-lg" name="cancelarDocumento" id="cancelarDocumento" required>

              </div>

            </div>

           <!-- ENTRADA PARA LA FECHA  --> 
            <div class="form-group col-lg-2">
            <div style="margin-top:23px"></div>
              <label for="">Fecha último pago</label>
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-calendar"></i></span> 

                <input type="date" class="form-control input-lg" name="cancelarFechaUltima" id="cancelarFechaUltima"  required>

              </div>

            </div>

            <!-- ENTRADA PARA LA NOTA -->
            
            <div class="form-group col-lg-3">
            <div style="margin-top:23px"></div>
            <label for=""><b>Notas</b></label>
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-text-width"></i></span> 

                <input type="text" class="form-control input-lg" name="cancelarNota" id="cancelarNota" required>

              </div>

            </div>
            
            
            <div class="form-group col-lg-3">
            <div style="margin-top:23px"></div>
            <label for="">Monto </label>
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-usd"></i></span> 

                <input type="number" min="0" step="any" class="form-control input-lg" name="cancelarMonto" id="cancelarMonto" >
                <input type="hidden"  id="cancelarSaldo" name="cancelarSaldo">
                <input type="text"  id="cancelarSaldoAntiguo" name="cancelarSaldoAntiguo">

              </div>

            </div>
  
          </div>

        </div>

        <!--=====================================
        PIE DEL MODAL
        ======================================-->

        <div class="modal-footer">

          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

          <button type="submit" class="btn btn-primary">Editar cancelacion</button>

        </div>

      </form>

      <?php

        $editarCancelacion = new ControladorCuentas();
        $editarCancelacion -> ctrEditarCancelacion();

      ?>   


    </div>

  </div>

</div>


<?php

  $eliminarCancelacion = new ControladorCuentas();
  $eliminarCancelacion -> ctrEliminarCancelacion();

?>

<script>
window.document.title = "Cancelaciones de cuenta"
</script>