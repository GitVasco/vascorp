<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Envio Letras

        </h1>

        <ol class="breadcrumb">

            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Envio Letras</li>

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

                <form role="form" method="post" class="formularioEnvioLetra">

                    <div class="box-body">

                    <div class="box">

                        <!--=====================================
                        ENTRADA DEL VENDEDOR
                        ======================================-->

                        <div class="form-group">

                        <div class="input-group">

                            <span class="input-group-addon"><i class="fa fa-user"></i></span>

                            <input type="text" class="form-control" id="usuario" name="usuario"
                            value="<?php echo $_SESSION["nombre"]; ?>" readonly>

                            <input type="hidden" name="idUsuario" value="<?php echo $_SESSION["id"]; ?>">                      

                        </div>

                        </div>


                        <!--=====================================
                        ENTRADA DEL CODIGO INTERNO
                        ======================================-->

                        <div class="form-group">

                        <div class="input-group">

                            <span class="input-group-addon"><i class="fa fa-key"></i></span>

                            <?php

                            $ult_codigo = ControladorCuentas::ctrUltimoCodigoEnvioLetra();

                            if (!$ult_codigo || !isset($ult_codigo["ultimo_codigo"])) {
                                echo '<input type="text" class="form-control" id="nuevoCodigoEnvio" name="nuevoCodigoEnvio" value="1001" readonly>';
                            } else {
                                $codigo = ((int) $ult_codigo["ultimo_codigo"]) + 1;
                                echo '<input type="text" class="form-control" id="nuevoCodigoEnvio" name="nuevoCodigoEnvio" value="'.$codigo.'" readonly>';
                            }


                        ?>

                        </div>

                        </div>

                        <!--=====================================
                        FORMATO ARCHIVO BANCO
                        ======================================-->
                        <div class="form-group">
                            <label>Formato para el banco</label>
                            <select class="form-control" name="formatoEnvioLetra" id="formatoEnvioLetra">
                                <option value="txt" selected>TXT — formato vigente (recomendado)</option>
                                <option value="excel">Excel nuevo — con dirección (pendiente confirmación banco)</option>
                            </select>
                            <p class="help-block" style="margin-bottom:0;">
                                Mientras el banco no confirme el Excel, use <b>TXT</b>.
                            </p>
                        </div>

                        <!--=====================================
                        TITULOS
                        ======================================-->
                        
                        <div class="box box-primary">

                        <div class="row">

                            <div class="col-xs-3">

                                <label>Num. cta</label>

                            </div>

                            <div class="col-xs-7">

                                <label>Cliente</label>

                            </div>

                            <div class="col-xs-2">

                                <label>Monto</label>

                            </div>
                        </div>

                        </div>
                
                        <!--=====================================
                        ENTRADA PARA AGREGAR MATERIAPRIMA
                        ======================================-->

                        <div class="form-group row nuevoCampoEnvio">


                        </div>

                        <input type="hidden" id="listaEnvioLetra" name="listaEnvioLetra">          

                        <div class="row">

                        <!--=====================================
                        ENTRADA IMPUESTOS Y TOTAL
                        ======================================-->

                        <div class="col-xs-6 pull-right">

                            <table class="table">

                            <thead>

                                <tr>
                                <th>Cantidad de letras</th>
                                </tr>

                            </thead>

                            <tbody>

                            <tr>

                                <td style="width: 50%">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-paper-plane-o"></i></span>

                                    <input type="text" min="1" class="form-control input-lg" id="nuevoTotalCuentaEnvio" name="nuevoTotalCuentaEnvio" total="" placeholder="0" readonly required>

                                    <input type="hidden" name="totalEnvioCuentas" id="totalEnvioCuentas">


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

                    <button type="submit" class="btn btn-primary pull-right"><i class="fa fa-floppy-o"></i>  Guardar Envio</button>
                    
                    <a href="ver-envio-letras" id="cancel" name="cancel" class="btn btn-danger"><i class="fa fa-times-circle"></i> Cancelar</a>
                    </div>

                </form>

                <?php

                 $crearEnvioLetras = new ControladorCuentas();
                 $crearEnvioLetras -> ctrCrearEnvioLetras();

                ?> 
                

                </div>

            </div>

            <div class="col-lg-7 col-xs-12">
                <div class="box box-warning">
                    <div class="box-body">
                        <table class="table table-bordered table-striped dt-responsive tablaEnvioLetras" width="100%">
                            
                            <thead>
                            
                            <tr>
                            <th style="width:10px">Tipo Doc.</th>
                            <th>Nro. cuenta</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th style="width:20px">Vencimiento</th>
                            <th>Monto</th>
                            <th>Acciones</th>
                            </tr> 

                            </thead>

                            <tbody>

                            </tbody>

                        </table>
                    </div>
                    
                
                </div>
            </div>
        </div>    
    </section>
    
</div>

<script>
window.document.title = "Enviar Letras"
</script>