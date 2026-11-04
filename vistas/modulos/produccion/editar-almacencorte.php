<div class="content-wrapper">

  <section class="content-header">

    <h1>

      Editar Corte

    </h1>

    <ol class="breadcrumb">

      <li><a href="#"><i class="fa fa-dashboard"></i> Inicio</a></li>

      <li class="active">Editar corte</li>

    </ol>

  </section>

  <section class="content">

    <div class="row">

      <!--=====================================
      EL FORMULARIO
      ======================================-->

      <div class="col-lg-7 col-xs-12">

        <div class="box box-success">

          <div class="box-header with-border"></div>

          <form role="form" method="post" class="formularioAlmacenCorte">

            <div class="box-body">

              <div class="box">
                <?php 
                    $valor = $_GET["codigo"];

                    $almacenCorte = ControladorAlmacenCorte::ctrMostrarAlmacenCorte($valor);

                    //var_dump($almacenCorte);
                ?>

                <!--=====================================
                ENTRADA DEL VENDEDOR
                ======================================-->

                <div class="form-group">

                  <div class="input-group">

                    <span class="input-group-addon"><i class="fa fa-user"></i></span>

                    <input type="text" class="form-control" id="editarUsuario" name="editarUsuario"
                      value="<?php echo $almacenCorte["nombre"]; ?>" readonly>

                    <input type="hidden" name="idUsuario" value="<?php echo $_SESSION["id"]; ?>">                      

                  </div>

                </div>

                <div class="form-group">

                  <div class="input-group">

                    <span class="input-group-addon"><i class="fa fa-file-text"></i></span>

                    <input type="text" class="form-control" id="editarGuia" name="editarGuia" value="<?php echo $almacenCorte["guia"]?>" required>                  

                  </div>

                </div>

                <!--=====================================
                ENTRADA DEL CODIGO INTERNO
                ======================================-->

                <div class="form-group">

                  <div class="input-group">

                    <span class="input-group-addon"><i class="fa fa-key"></i></span>

             
                        <input type="text" class="form-control" id="editarAlmacenCorte" name="editarAlmacenCorte" value="<?php echo $almacenCorte["codigo"]?>" readonly>


                  </div>

                  

                </div>

                <!--=====================================
                  ENTRADA BUSCADOR
                  ======================================-->

                  <div class=" form-group buscador" id="elid" style="padding-bottom:25px">
                    <label for="" class="col-form-label col-lg-1">Modelo:</label>
                    <div class="col-lg-4">
                        <select class="form-control" id="filtroModeloAC">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <label for="" class="col-form-label col-lg-1">Buscar:</label>
                    <div class="col-lg-6">
                        <div class="input-group">
                            
                            <input type="text" class="form-control " id="buscadorAc" name="buscadorAc"/>
                            <div class="input-group-addon"><i class="fa fa-search"></i></div>
                        </div>
                    </div>
                        
                  </div>

                <!--=====================================
                TITULOS
                ======================================-->
                
                <div class="box box-primary">

                  <div class="row">

                      <div class="col-xs-3">

                          <label>OC</label>

                      </div>

                      <div class="col-xs-5">

                          <label>Artículo</label>

                      </div>

                      <div class="col-xs-2">

                          <label>Cantidad</label>

                      </div>

                      <div class="col-xs-2">

                          <label>Saldo</label>

                      </div>

                  </div>

                </div>
                
         
                <!--=====================================
                ENTRADA PARA AGREGAR ARTICULO
                ======================================-->

                <div class="form-group row nuevoArticuloAC" style="height:400px;overflow: scroll; overflow-x:hidden">
                  <?php

                    $item = "almacencorte";
                    $valor = $almacenCorte["codigo"];
                    $listaArticuloCorte= ControladorAlmacenCorte::ctrMostrarDetallesAlmacenCorte($item,$valor);

                    // var_dump($listaArticuloCorte);

                    foreach ($listaArticuloCorte as $key => $value) {

                        $infoProducto=controladorArticulos::ctrMostrarArticulos($value["articulo"]);

                        $saldoAntiguo = $value["saldo"] + $value["cantidad"];
                        $almacenAntiguo = $infoProducto["alm_corte"]-$value["cantidad"];
                        

                        echo '<div class="row munditoAC" data-modelo="'.htmlspecialchars(trim((string)$infoProducto["modelo"]), ENT_QUOTES).'" style="padding:5px 15px">
    
                        <div class="col-xs-3" style="padding-right:0px">
    
                            <div class="input-group">
                            
                                <span class="input-group-addon"><button type="button" class="btn btn-danger btn-xs quitarAC" idCorte="'.$value["detordencorte"].'"><i class="fa fa-times"></i></button></span>
    
                                <input type="text" class="form-control nuevoAlmacenCorte"   name="nrooc" value="N° - '.$value["ordencorte"].'" idCorte="'.$value["detordencorte"].'" ordcorte="'.$value["ordencorte"].'" readonly required>
    
                            </div>
    
                        </div>
    
                        <div class="col-xs-5" style="padding-right:0px">                     
    
                                <input type="text" class="form-control nuevaDescripcionProducto" articuloAC="'.$infoProducto["articulo"].'" name="agregarAC" value="'.$infoProducto["packing"].'" codigoAC="'.$infoProducto["articulo"].'" readonly required>                       
    
                        </div>
    
                        <div class="col-xs-2">
    
                            <input type="number" class="form-control nuevaCantidadArticuloAC" name="nuevaCantidadArticuloAC" min="1" value="'.$value["cantidad"].'" ordcorte="'.$value["ordencorte"].'" saldo="'.$saldoAntiguo.'" nuevoSaldo="'.$value["saldo"].'" alm_corte="'.$almacenAntiguo.'" nuevoAlmCorte="'.$value["cantidad"].'" cantidad = "'.$value["cantidad"].'" nuevaCantidad = "0" required>
    
                        </div>
    
                        <div class="col-xs-2 ingresoSaldo">
    
                            <input type="number" class="form-control nuevaCantidadSaldo" name="nuevaCantidadSaldo" saldoReal="'.$saldoAntiguo.'" nuevoSaldoP="'.$value["saldo"].'" value="'.$value["saldo"].'" readonly required>
    
                        </div>                    
                    
                    </div>';
                    }
                  ?>

                </div>

                <input type="hidden" id="listaArticulosAC" name="listaArticulosAC">
                <input type="hidden" id="listArticulo" name="listArticulo">
                

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

                            <input type="text" min="1" class="form-control input-lg" id="nuevoTotalAlmacenCorte" name="nuevoTotalAlmacenCorte" total="<?php echo $almacenCorte["total"]?>" value="<?php echo $almacenCorte["total"]?>" placeholder="0" readonly required>

                            <input type="hidden" name="totalAlmacenCorte" id="totalAlmacenCorte" value="<?php echo $almacenCorte["total"]?>">


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

              <button type="submit" class="btn btn-primary pull-right"><i class="fa fa-floppy-o"></i>  Guardar Corte</button>
              
              <a href="almacencorte" id="cancel" name="cancel" class="btn btn-danger"><i class="fa fa-times-circle"></i> Cancelar</a>
            </div>

          </form>

          <?php

          $editarAlmacenCorte = new ControladorAlmacenCorte();
          $editarAlmacenCorte -> ctrEditarAlmacenCorte();

          ?> 
          

        </div>

      </div>

      <!--=====================================
      LA TABLA DE ARTICULOS
      ======================================-->

      <div class="col-lg-5 hidden-md hidden-sm hidden-xs">

        <div class="box box-warning">

          <div class="box-header with-border"></div>

          <div class="box-body">

            <div class="form-group">
              <label for="filtroModeloTablaAC">Filtrar por modelo:</label>
              <select class="form-control" id="filtroModeloTablaAC">
                <option value="">Todos</option>
              </select>
            </div>

            <table class="table table-bordered table-striped table-condensed tablaArticulosAlmacenCorte" width="100%">

              <thead>

                <tr>
                  <th style="width:10px">Acciones</th>
                  <th>OC</th>
                  <th>Modelo</th>
                  <th>Color</th>
                  <th>Talla</th>
                  <th>Cantidad</th>
                  <th>Saldo</th>
                  <th>Alm. Corte</th>
                  
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
window.document.title = "Editar cortes"
</script>

<script>
/*
 * FILTRO POR MODELO + BUSCADOR en los artículos ya registrados en el corte.
 * Solo oculta/muestra filas: todas siguen en el formulario y se guardan igual.
 */
$(function () {

    var filas = $('.nuevoArticuloAC .munditoAC');
    var selModelo = $('#filtroModeloAC');

    var modelos = [];
    filas.each(function () {
        var m = $(this).attr('data-modelo');
        if (m && modelos.indexOf(m) === -1) { modelos.push(m); }
    });
    modelos.sort();
    modelos.forEach(function (m) {
        selModelo.append($('<option>').val(m).text(m));
    });

    function filtrarRegistrados() {
        var modelo = selModelo.val();
        var texto = ($('#buscadorAc').val() || '').toLowerCase();

        filas.each(function () {
            var fila = $(this);
            var descripcion = (fila.find('.nuevaDescripcionProducto').val() || '').toLowerCase();
            var okModelo = modelo === '' || fila.attr('data-modelo') === modelo;
            var okTexto = texto === '' || descripcion.indexOf(texto) > -1;
            fila.toggle(okModelo && okTexto);
        });
    }

    $('#buscadorAc').on('keyup', filtrarRegistrados);
    selModelo.on('change', filtrarRegistrados);

    /*
     * FILTRO POR MODELO en la tabla de artículos disponibles (columna 2 = Modelo)
     */
    var tabla = $('.tablaArticulosAlmacenCorte');
    var selTabla = $('#filtroModeloTablaAC');

    function llenarModelosTabla(datos) {
        var lista = [];
        (datos || []).forEach(function (fila) {
            var m = $.trim(fila[2]);
            if (m && lista.indexOf(m) === -1) { lista.push(m); }
        });
        lista.sort();
        selTabla.find('option:not(:first)').remove();
        lista.forEach(function (m) {
            selTabla.append($('<option>').val(m).text(m));
        });
    }

    tabla.on('xhr.dt', function (e, settings, json) {
        llenarModelosTabla(json && json.data);
    });

    // por si la tabla ya cargó antes de enganchar el evento
    if ($.fn.dataTable.isDataTable(tabla)) {
        llenarModelosTabla(tabla.DataTable().rows().data().toArray());
    }

    selTabla.on('change', function () {
        var v = $(this).val();
        var dt = tabla.DataTable();
        dt.column(2).search(v === '' ? '' : '^' + v.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '$', true, false).draw();
    });
});
</script>
