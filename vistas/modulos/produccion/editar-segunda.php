<div class="content-wrapper">

  <section class="content-header">

    <h1>

      Editar segunda

    </h1>

    <ol class="breadcrumb">

      <li><a href="#"><i class="fa fa-dashboard"></i> Inicio</a></li>

      <li class="active">Editar segunda</li>

    </ol>

  </section>

  <section class="content">

    <div class="row">

      <!--=====================================
      EL FORMULARIO
      ======================================-->

      <div class="col-lg-5 col-xs-12">

        <div class="box box-success">

          <div class="box-header with-border"></div>

          <form role="form" method="post" class="formularioIngreso">

            <div class="box-body">

              <div class="box">

              <?php
              
              $item = "id";
              $valor = $_GET["idIngreso"];

              $ingreso = ControladorIngresos::ctrMostrarIngresos($item, $valor);
              ?>
                <!--=====================================
                ENTRADA DEL VENDEDOR
                ======================================-->

                <div class="form-group">

                  <div class="input-group">

                    <span class="input-group-addon"><i class="fa fa-user"></i></span>

                    <input type="text" class="form-control" id="usuario" name="usuario"
                      value="<?php echo $_SESSION["nombre"]; ?>" readonly>

                    <input type="hidden" name="idUsuario" value="<?php echo $_SESSION["id"]; ?>">   
                    <input type="hidden" name="idIngreso" value="<?php echo $_GET["idIngreso"]; ?>">                    

                  </div>

                </div>

                <!--=====================================
                ENTRADA DEL CODIGO INTERNO
                ======================================-->

                <div class="form-group">

                  <div class="input-group">

                    <span class="input-group-addon"><i class="fa fa-key"></i></span>
                    <input type="text" class="form-control" id="editarCodigo" name="editarCodigo" value="<?php echo $ingreso["documento"]; ?>" readonly>
                    <input type="hidden" id="pasarTaller" value="<?php echo $ingreso["taller"]; ?>" >  

                  </div>

                </div>

                <!--=====================================
                ENTRADA DEL ARTICULO
                ======================================-->

                <div class="form-group">

                    <div class="input-group">

                        <span class="input-group-addon"><i class="fa fa-wrench"></i></span>
                        <input class="form-control  input-sm selectpicker" name="editarTalleres" id="editarTalleres" data-live-search="true" readonly>
                        <input type="hidden" id="editarTipoSector" name="editarTipoSector">

                    </div>

                </div>

                <div class="form-group">

                    <div class="input-group">
                        <input type="hidden" name="pasarTra" id="pasarTra" value=<?php echo $ingreso["trabajador"]?>>
                        <span class="input-group-addon"><i class="fa fa-user"></i></span>
                        <select class="form-control  input-sm selectpicker" name="editarTrabajadores" id="editarTrabajadores" data-live-search="true"   >
                        
                        <?php
                            $sector=ControladorTrabajador::ctrMostrarTrabajador(null,null);
                            foreach ($sector as $key => $value) {

                                echo '<option value="' . $value["cod_tra"] . '">' . $value["nom_tra"]." ".$value["ape_pat_tra"]." ".$value["ape_mat_tra"].'</option>';
          
                              }

                            

                        ?>
                        </select>

                    </div>

                </div>    

                <!--=====================================
                ENTRADA BUSCADOR
                ======================================-->

                <div class=" form-group buscador" id="elid" style="padding-bottom:25px">
                  <label for="" class="col-form-label col-lg-1">Buscar:</label>
                  <div class="col-lg-11">
                      <div class="input-group">
                          
                          <input type="text" class="form-control " id="buscadorIngreso" name="buscadorIngreso"/>
                          <div class="input-group-addon"><i class="fa fa-search"></i></div>
                      </div>
                  </div>
                      
                </div>         

                <!--=====================================
                TITULOS
                ======================================-->
                
                <div class="box box-primary">

                  <div class="row">

                    <div class="col-xs-6">

                      <label>Articulo</label>

                    </div>

                    <div class="col-xs-3">

                      <label for="">En Taller</label>

                    </div>

                    <div class="col-xs-3">

                      <label for="">Saldo</label>

                    </div>

                  </div>

                </div>
         
                <!--=====================================
                ENTRADA PARA AGREGAR MATERIAPRIMA
                ======================================-->

                <div class="form-group row nuevoArticuloIngreso" style="height:400px;overflow: scroll; overflow-x:hidden">
                  
                <?php

                  $listaArticuloIng = ControladorIngresos::ctrMostrarDetallesIngresos("documento",$ingreso["documento"]);
                  #var_dump("ordencorte", $ordencorte["codigo"]);
                  #var_dump("listaArticuloOC", $listaArticuloOC);
                  foreach($listaArticuloIng as $key=>$value){

                    if (ControladorSectores::ctrEsInterno($ingreso["taller"])) {

                      $infoArticulo = ControladorArticulos::ctrMostrarArticulos($value["articulo"]);
                    }else{
                      $infoArticulo = ControladorIngresos::ctrMostrarArticulosCierres($value["idcierre"]);
                    }
                    $tallerAntiguo = $infoArticulo["taller"] + $value["cantidad"];
                    $stockG = $infoArticulo["stockG"];
                    if (ControladorSectores::ctrEsInterno($ingreso["taller"])) {
                      echo '<div class="row munditoIngreso" style="padding:5px 15px">

                            <div class="col-xs-6" style="padding-right:0px">
                        
                              <div class="input-group">
                        
                                <span class="input-group-addon"><button type="button" class="btn btn-danger btn-xs quitarTaller" articuloIngreso="'.$infoArticulo["articulo"].'"><i class="fa fa-times"></i></button></span>
                        
                                <input type="text" class="form-control nuevaDescripcionProducto input-sm" articuloIngreso="'.$infoArticulo["articulo"].'" name="agregarT" value="'.$infoArticulo["packing"].'" codigoAC="'.$infoArticulo["articulo"].'" idCierre= "'.$value["idcierre"].'" readonly required>
                        
                              </div>
                        
                            </div>
                        
                            <div class="col-xs-3">
                        
                              <input type="number" class="form-control nuevaCantidadArticuloIngreso input-sm" name="nuevaCantidadArticuloIngreso" id="nuevaCantidadArticuloIngreso" min="1" value="'.$value["cantidad"].'" taller="'.$tallerAntiguo.'" articulo="'.$infoArticulo["articulo"].'" nuevotaller="'.$infoArticulo["taller"].'" nuevaCantidad ="'.$value["cantidad"].'" cantidad="" required>
                        
                            </div>
                            
                            <div class="col-xs-3 divSaldoIngreso">
                        
                              <input type="number" class="form-control nuevoSaldoIngreso input-sm" name="nuevoSaldoIngreso" id="nuevoSaldoIngreso" min="1" value="'.$infoArticulo["taller"].'" readonly>
                        
                            </div>';
                            echo '</div>';  
                    }else{

                      echo '<div class="row munditoIngreso" style="padding:5px 15px">

                            <div class="col-xs-6" style="padding-right:0px">
                        
                              <div class="input-group">
                        
                                <span class="input-group-addon"><button type="button" class="btn btn-danger btn-xs quitarTaller" articuloIngreso="'.$value["idcierre"].'"><i class="fa fa-times"></i></button></span>
                        
                                <input type="text" class="form-control nuevaDescripcionProducto input-sm" articuloIngreso="'.$infoArticulo["articulo"].'" name="agregarT" value="'.$infoArticulo["packing"].'" codigoAC="'.$infoArticulo["articulo"].'" idCierre= "'.$value["idcierre"].'" readonly required>
                        
                              </div>
                        
                            </div>
                        
                            <div class="col-xs-3">
                        
                              <input type="number" class="form-control nuevaCantidadArticuloIngreso input-sm" name="nuevaCantidadArticuloIngreso" id="nuevaCantidadArticuloIngreso" min="1" value="'.$value["cantidad"].'" taller="'.$tallerAntiguo.'" articulo="'.$infoArticulo["articulo"].'" nuevotaller="'.$infoArticulo["taller"].'" nuevaCantidad="'.$value["cantidad"].'" cantidad="" required>
                        
                            </div>
                            
                            <div class="col-xs-3 divSaldoIngreso">
                        
                              <input type="number" class="form-control nuevoSaldoIngreso input-sm" name="nuevoSaldoIngreso" id="nuevoSaldoIngreso" min="1" value="'.$infoArticulo["taller"].'" readonly>
                        
                            </div>';
                            echo '</div>';  
                      
                    }
                  }
                ?>
                  

                </div>

                <input type="hidden" id="listaArticulosIngreso" name="listaArticulosIngreso">                

                <div class="row">

                  <!--=====================================
                  ENTRADA IMPUESTOS Y TOTAL
                  ======================================-->

                  <div class="col-xs-6 pull-right">

                    <table class="table">

                      <thead>

                        <tr>
                          <th>Total</th>
                        </tr>

                      </thead>

                      <tbody>

                      <tr>

                        <td style="width: 50%">

                          <div class="input-group">

                            <span class="input-group-addon"><i class="fa fa-scissors"></i></span>

                            <input type="text" min="1" class="form-control input-lg" id="nuevoTotalTaller"
                              name="nuevoTotalTaller" total="" placeholder="0"  total="<?php echo $ingreso["total"]; ?>" value="<?php echo $ingreso["total"]?>" readonly required>

                            <input type="hidden" name="totalTaller" id="totalTaller" value="<?php echo $ingreso["total"]?>">


                          </div>

                        </td>

                      </tr>

                      </tbody>

                    </table>

                  </div>

                </div>

                <hr>

                <!--=====================================
                BOTON GUARDAR
                ======================================-->

                <br>

              </div>

            </div>

            <div class="box-footer">

              <button type="submit" class="btn btn-primary pull-right"><i class="fa fa-floppy-o"></i>  Guardar cambios</button>
              
              <a href="ingresos" id="cancel" name="cancel" class="btn btn-danger"><i class="fa fa-times-circle"></i> Cancelar</a>
            </div>

          </form>

          <?php

            $editarSegunda = new ControladorIngresos();
            $editarSegunda -> ctrEditarSegunda();
                  
          ?>            
          

        </div>

      </div>

      <!--=====================================
      LA TABLA DE ARTICULOS
      ======================================-->

      <div class="col-lg-7 hidden-md hidden-sm hidden-xs">

        <div class="box box-warning">

          <div class="box-header with-border"></div>

          <div class="box-body">

            <table class="table table-bordered table-striped table-condensed tablaArticulosTalleres" width="100%">

              <thead>

                <tr>
                  <th class="text-center">Guia</th>
                  <th class="text-center">Modelo</th>
                  <th class="text-center">Color</th>
                  <th class="text-center">Talla</th>
                  <th class="text-center">Stock</th>
                  <th class="text-center">En Taller</th>
                  <th class="text-center">Alm. Corte</th>
                  <th class="text-center">Ord. Corte</th>
                  <th class="text-center">Acciones</th>
                </tr>

              </thead>



            </table>

          </div>

        </div>


      </div>

    </div>

  </section>

</div>

<script>
$(document).ready(function(){
  pasar=$("#pasarTaller").val();          
  $("#editarTalleres").val(pasar);
  $("#editarTalleres").selectpicker("refresh");
  pasarTra=$("#pasarTra").val();    
  $("#editarTrabajadores").val(pasarTra);
  $("#editarTrabajadores").selectpicker("refresh");
})
</script>
<script>
window.document.title = "Editar segunda"
</script>

<script>
$('.nuevoArticuloIngreso').ready(function(){
    $('#buscadorIngreso').keyup(function(){


    var nombres = $('.nuevaDescripcionProducto');
    //console.log(nombres.val())
    //console.log(nombres.length())

    var buscando = $(this).val();
    //console.log(buscando.length);

    var item='';

       for( var i = 0; i < nombres.length; i++ ){

        item = $(nombres[i]).val();
        item2 = $(nombres[i]).val().toLowerCase();
        // console.log(item);

            for(var x = 0; x < item.length; x++ ){

                if( buscando.length == 0 || item.indexOf( buscando ) > -1 || item2.indexOf( buscando ) > -1 ){

                    $(nombres[i]).parents('.munditoIngreso').show(); 

                }else{

                    $(nombres[i]).parents('.munditoIngreso').hide();

                }
            }

          
       }

       
    });
  });

  $(document).ready(function(){
    var ingreso = $("#editarTalleres").val();
    var datos = new FormData();
    datos.append("idSector", ingreso);
    $.ajax({

          url: "ajax/sectores.ajax.php",
          method: "POST",
          data: datos,
          cache: false,
          contentType: false,
          processData: false,
          dataType: "json",
          success: function (respuesta) {

              //console.log("respuesta", respuesta);

              $("#editarTipoSector").val(respuesta["tipo"]);

          }

      })
  })
</script>