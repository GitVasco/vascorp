<?php
$gruposEmpresariales = ControladorGruposEmpresariales::ctrMostrarGruposActivos();
$categoriasComercialesActivas = ControladorCategoriasClientes::ctrListarCategoriasActivas();
if (!is_array($categoriasComercialesActivas)) {
    $categoriasComercialesActivas = array();
}
?>
<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Administrar clientes

        </h1>

        <ol class="breadcrumb">

            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Administrar clientes</li>

        </ol>

    </section>

    <section class="content">

        <div class="box">

            <div class="box-header with-border">

                <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarCliente">

                    Agregar cliente

                </button>

            </div>

            <div class="box-body">

                <input type="hidden" value="<?= $_SESSION["perfil"]; ?>" id="perfilOculto">

                <table class="table table-bordered table-striped dt-responsive tablaClientes" width="100%">

                    <thead>

                        <tr>

                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Tip. Pers.</th>
                            <th>Tip. Doc.</th>
                            <th>Documento</th>
                            <th>Teléfono</th>
							<th>Categoría</th>
                            <th>Ingreso al sistema</th>
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
MODAL AGREGAR CLIENTE
======================================-->

<div id="modalAgregarCliente" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 85% !important;">

        <div class="modal-content">

            <form role="form" method="post">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Agregar cliente</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">

                        <!-- DATOS PRINCIPALES -->

                        <div class="box box-primary col-lg-12 ">

                            <div class="box-header">

                                <b>Datos Principales</b>

                            </div>
                            <!-- ENTRADA PARA EL TIPO DOCUMENTO -->

                            <div class="form-group col-lg-2">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <select class="form-control input-sm" id="tipo_documento" name="tipo_documento" required>

                                        <option value="">TIPO DOCUMENTO</option>

                                        <option value="0">SIN DOCUMENTO</option>
                                        <option value="1">DNI</option>
                                        <option value="4">C. Extra.</option>
                                        <option value="6">RUC</option>
                                        <option value="7">PASAPORTE</option>
                                        <option value="A">C. Diplom.</option>
                                        <option value="B">Doc.Pais Residencia</option>
                                        <option value="C">Tax Identification Number</option>
                                        <option value="D">Identification Number</option>
                                        <option value="E">Tarjeta Andina de Migracion</option>

                                    </select>

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL NUMERO DEL DOCUMENTO -->

                            <div class="form-group col-lg-2">

                                <div class="input-group">



                                    <input type="text" class="form-control input-sm" name="documento" id="documentoCliente" placeholder="NRO. DOCUMENTO" required>
                                    <span class="input-group-addon" style="padding:0px !important;border: 0px !important"><button type="button" class="btn btn-sm btn-default" onclick="ObtenerDatosCliente()"><i class="fa fa-search "></i></button> </span>
                                </div>

                            </div>


                            <!-- ENTRADA PARA EL CODIGO -->

                            <div class="form-group col-lg-2">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <input type="text" class="form-control input-sm info-box-text" name="codigoCliente" id="codigoCliente" placeholder="Código" required>

                                </div>

                            </div>

                            <!-- ENTRADA PARA RAZON SOCIAL -->

                            <div class="form-group col-lg-6">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <input type="text" class="form-control input-sm info-box-text" name="nombre" id="nuevaRazPro" placeholder="Razón Social o Nombre Completo" required>

                                </div>

                            </div>


                            <!-- ENTRADA PARA EL TIPO PERSONA -->

                            <div class="form-group col-lg-2">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <select class="form-control input-sm" id="tipo_persona" name="tipo_persona" required>

                                        <option value="">TIPO PERSONA</option>

                                        <option value="1">NATURAL</option>
                                        <option value="2">JURÍDICA</option>

                                    </select>

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL APELLIDO PATERNO -->

                            <div class="form-group col-lg-3">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <input type="text" class="form-control input-sm info-box-text" name="ape_paterno" id="ape_paterno" placeholder="Apellido Paterno">

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL APELLIDO MATERNO -->

                            <div class="form-group col-lg-3">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <input type="text" class="form-control input-sm info-box-text" name="ape_materno" id="ape_materno" placeholder="Apellido Materno">

                                </div>

                            </div>

                            <!-- ENTRADA PARA NOMBRES -->

                            <div class="form-group col-lg-4">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <input type="text" class="form-control input-sm info-box-text" name="nombres" id="nombres" placeholder="Nombres">

                                </div>

                            </div>


                        </div>

                        <!-- FIN DATOS PRINCIPALES -->

                        <!-- DATOS DIRECCION PRINCIPAL-->

                        <div class="box box-warning col-lg-12 ">

                            <div class="box-header">

                                <b>Dirección Facturación</b>

                            </div>

                            <!-- ENTRADA PARA LA DIRECCION -->

                            <div class="form-group col-lg-8">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <input type="text" class="form-control input-sm info-box-text" name="direccion" id="nuevaDireccion" placeholder="Direccion de Facturación" required>

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL UBIGEO -->

                            <div class="form-group col-lg-4">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <select class="form-control input-sm selectpicker" id="nuevoUbiPro" name="ubigeo" data-live-search="true" data-size="10" required>

                                        <option value="">UBIGEO</option>

                                        <?php

                                        $ubigeo = ControladorClientes::ctrMostrarUbigeos();
                                        #var_dump("ubigeo", $ubigeo);
                                        foreach ($ubigeo as $key => $value) {

                                            echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . ' - ' . $value["ubigeo"] . '</option>';
                                        }


                                        ?>



                                    </select>

                                </div>

                            </div>

                            <!-- </div> -->

                            <!-- DATOS DIRECCION SECUNDARIA-->

                            <!-- <div class="box box-danger col-lg-12 "> -->

                            <div class="box-header">

                                <b>Dirección Despacho</b>

                            </div>

                            <!-- ENTRADA PARA LA DIRECCION -->

                            <div class="form-group col-lg-8">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <input type="text" class="form-control input-sm info-box-text" name="direccionDespacho" id="nuevaDireccion" placeholder="Direccion de Despacho">

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL UBIGEO -->

                            <div class="form-group col-lg-4">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <select class="form-control input-sm selectpicker" id="nuevoUbiPro" name="ubigeoDespacho" data-live-search="true" data-size="10">

                                        <option value="">UBIGEO</option>

                                        <?php

                                        $ubigeo = ControladorClientes::ctrMostrarUbigeos();
                                        #var_dump("ubigeo", $ubigeo);
                                        foreach ($ubigeo as $key => $value) {

                                            echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . ' - ' . $value["ubigeo"] . '</option>';
                                        }


                                        ?>



                                    </select>

                                </div>

                            </div>

                        </div>

                        <!-- FIN DATOS DIRECCION -->

                        <!-- DATOS DIRECCION -->

                        <div class="box box-success col-lg-12 ">

                            <div class="box-header">

                                <b>CONTACTO</b>

                            </div>

                            <!-- ENTRADA PARA EL TELEFONO 1 -->

                            <div class="form-group col-lg-2">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <input type="text" class="form-control input-sm" name="telefono" placeholder="Telefono - 1">

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL TELEFONO 1 -->

                            <div class="form-group col-lg-2">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <input type="text" class="form-control input-sm" name="telefono2" placeholder="Telefono - 2">

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL E-MAIL -->

                            <div class="form-group col-lg-4">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <input type="text" class="form-control input-sm" name="email" placeholder="E - mail">

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL CONTACTO -->

                            <div class="form-group col-lg-4">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <input type="text" class="form-control input-sm" name="contacto" placeholder="Contacto">

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL VENDEDOR -->

                            <div class="form-group col-lg-4">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <select class="form-control input-sm selectpicker" id="vendedor" name="vendedor" data-size="5" data-live-search="true">

                                        <option value="">Seleccionar Vendedor</option>
                                        <?php
                                        $item = null;
                                        $valor = null;
                                        $vendedor = ControladorVendedores::ctrMostrarVendedores($item, $valor);
                                        foreach ($vendedor as $key => $value) {
                                            echo "<option value='" . $value["codigo"] . "'>" . $value["codigo"] . " - " . $value["descripcion"] . "</option>";
                                        }
                                        ?>

                                    </select>

                                </div>

                            </div>

                            <!-- ENTRADA PARA LOS GRUPOS -->

                            <div class="form-group col-lg-2">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <select class="form-control input-sm" id="grupo" name="grupo">

                                        <option value="">Grupo</option>
                                        <?php foreach ($gruposEmpresariales as $grupoItem) : ?>
                                            <option value="<?php echo htmlspecialchars($grupoItem["codigo"]); ?>">
                                                <?php echo htmlspecialchars($grupoItem["nombre"]); ?>
                                            </option>
                                        <?php endforeach; ?>

                                    </select>

                                </div>

                            </div>

                            <!-- ENTRADA PARA LA LISTA DE PRECIOS -->

                            <div class="form-group col-lg-2">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <select class="form-control input-sm" id="lista_precios" name="lista_precios" required>

                                        <option value="">Lista de Precios</option>
                                        <option value="precio1">Lista - 01</option>
                                        <option value="precio2">Lista - 02</option>
                                        <option value="precio3">Lista - 03</option>
                                        <option value="precio4">Lista - 04</option>
                                        <option value="precio5">Lista - 05</option>
                                        <option value="precio6">Lista - 06</option>
                                        <option value="precio7">Lista - 07</option>
                                        <option value="precio8">Lista - 08</option>
                                        <option value="precio9">Lista - 09</option>
                                        <option value="precio10">Lista - 10</option>
                                        <option value="precio11">Lista - 11</option>


                                    </select>

                                </div>

                            </div>

                            <!-- ENTRADA PARA AGENCIA DE TRANSPORTES-->

                            <div class="form-group col-lg-4">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <select class="form-control selectpicker" id="agencia" name="agencia" data-live-search="true">

                                        <option value="">Agencia de Transportes</option>
                                        <?php

                                        $agencia = ControladorAgencias::ctrMostrarAgencias(null, null);

                                        //var_dump($agencia);

                                        foreach ($agencia as $key => $value) {

                                            echo '<option value="' . $value["id"] . '">' . $value["id"] . ' - ' . $value["nombre"] . '</option>';
                                        }

                                        ?>

                                    </select>

                                </div>

                            </div>

                            <!-- CATEGORÍA COMERCIAL (ALTA) -->
                            <div class="form-group col-lg-2" id="bloqueCategoriaComercialNuevo">
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-tags"></i></span>
                                    <select class="form-control input-sm selectpicker" id="categoriaComercialNueva" name="categoria_comercial" data-live-search="true" title="Categoría comercial">
                                        <option value="">Categoría comercial</option>
                                        <?php foreach ($categoriasComercialesActivas as $catItem) : ?>
                                            <option value="<?php echo (int) $catItem["id"]; ?>">
                                                <?php echo htmlspecialchars($catItem["nombre"]); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>



                        </div>

                        <!-- FIN DATOS DIRECCION -->



                    </div>

                </div>

                <!--=====================================
        PIE DEL MODAL
        ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" class="btn btn-primary">Guardar cliente</button>

                </div>

            </form>

            <?php

            $crearCliente = new ControladorClientes();
            $crearCliente->ctrCrearCliente();

            ?>

        </div>

    </div>

</div>

<!--=====================================
MODAL EDITAR CLIENTE
======================================-->

<div id="modalEditarCliente" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 85% !important;">

        <div class="modal-content">

            <form role="form" method="post">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Editar cliente</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">

                        <!-- DATOS PRINCIPALES -->

                        <div class="box box-primary col-lg-12 ">

                            <div class="box-header">

                                <b>Datos Principales</b>

                            </div>

                            <!-- ENTRADA PARA EL CODIGO -->

                            <div class="form-group col-lg-2">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <input type="text" class="form-control input-sm" name="editarCodigoCliente" id="editarCodigoCliente" placeholder="Código" readonly required>

                                </div>

                            </div>

                            <!-- ENTRADA PARA RAZON SOCIAL -->

                            <div class="form-group col-lg-6">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <input type="text" class="form-control input-sm info-box-text" name="editarNombre" id="editarNombre" placeholder="Razón Social o Nombre Completo" required>

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL TIPO DOCUMENTO -->

                            <div class="form-group col-lg-2">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <select class="form-control input-sm" id="editarTipo_documento" name="editarTipo_documento" required>

                                        <option value="">TIPO DOCUMENTO</option>

                                        <option value="0">SIN DOCUMENTO</option>
                                        <option value="1">DNI</option>
                                        <option value="4">C. Extra.</option>
                                        <option value="6">RUC</option>
                                        <option value="7">PASAPORTE</option>
                                        <option value="A">C. Diplom.</option>
                                        <option value="B">Doc.Pais Residencia</option>
                                        <option value="C">Tax Identification Number</option>
                                        <option value="D">Identification Number</option>
                                        <option value="E">Tarjeta Andina de Migracion</option>

                                    </select>

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL NUMERO DEL DOCUMENTO -->

                            <div class="form-group col-lg-2">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <input type="number" class="form-control input-sm" id="editarDocumento" name="editarDocumento" placeholder="NRO. DOCUMENTO" required>

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL TIPO PERSONA -->

                            <div class="form-group col-lg-2">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <select class="form-control input-sm" id="editarTipo_persona" name="editarTipo_persona" required>

                                        <option value="">TIPO PERSONA</option>

                                        <option value="1">NATURAL</option>
                                        <option value="2">JURIDICA</option>

                                    </select>

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL APELLIDO PATERNO -->

                            <div class="form-group col-lg-3">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <input type="text" class="form-control input-sm info-box-text" name="editarApe_paterno" id="editarApe_paterno" placeholder="Apellido Paterno">

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL APELLIDO MATERNO -->

                            <div class="form-group col-lg-3">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <input type="text" class="form-control input-sm info-box-text" name="editarApe_materno" id="editarApe_materno" placeholder="Apellido Materno">

                                </div>

                            </div>

                            <!-- ENTRADA PARA NOMBRES -->

                            <div class="form-group col-lg-4">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <input type="text" class="form-control input-sm info-box-text" name="editarNombres" id="editarNombres" placeholder="Nombres">

                                </div>

                            </div>


                        </div>

                        <!-- FIN DATOS PRINCIPALES -->

                        <!-- DATOS DIRECCION PRINCIPAL-->

                        <div class="box box-warning col-lg-12 ">

                            <div class="box-header">

                                <b>Dirección Principal</b>

                            </div>

                            <!-- ENTRADA PARA LA DIRECCION-->

                            <div class="form-group col-lg-8">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <input type="text" class="form-control input-sm info-box-text" name="editarDireccion" id="editarDireccion" placeholder="Direccion de Facturación" required>

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL UBIGEO -->

                            <div class="form-group col-lg-4">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <select class="form-control input-sm selectpicker" id="editarUbigeo" name="editarUbigeo" data-live-search="true" data-size="10" required>

                                        <?php

                                        $ubigeo = ControladorClientes::ctrMostrarUbigeos();
                                        #var_dump("ubigeo", $ubigeo);

                                        foreach ($ubigeo as $key => $value) {

                                            echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . ' - ' . $value["ubigeo"] . '</option>';
                                        }


                                        ?>



                                    </select>

                                </div>

                            </div>

                            <!-- </div> -->

                            <!-- DATOS DIRECCION  DESPACHO-->

                            <!-- <div class="box box-warning col-lg-12 "> -->

                            <div class="box-header">

                                <b>Dirección Despacho</b>

                            </div>

                            <!-- ENTRADA PARA LA DIRECCION-->

                            <div class="form-group col-lg-8">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <input type="text" class="form-control input-sm info-box-text" name="editarDireccionDespacho" id="editarDireccionDespacho" placeholder="Direccion de despacho">

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL UBIGEO -->

                            <div class="form-group col-lg-4">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <select class="form-control input-sm selectpicker" id="editarUbigeoDespacho" name="editarUbigeoDespacho" data-live-search="true" data-size="10">

                                        <?php

                                        $ubigeo = ControladorClientes::ctrMostrarUbigeos();
                                        #var_dump("ubigeo", $ubigeo);

                                        foreach ($ubigeo as $key => $value) {

                                            echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . ' - ' . $value["ubigeo"] . '</option>';
                                        }


                                        ?>



                                    </select>

                                </div>

                            </div>

                        </div>

                        <!-- FIN DATOS DIRECCION -->

                        <!-- DATOS DIRECCION -->

                        <div class="box box-success col-lg-12 ">

                            <div class="box-header">

                                <b>CONTACTO</b>

                            </div>

                            <!-- ENTRADA PARA EL TELEFONO 1 -->

                            <div class="form-group col-lg-2">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <input type="text" class="form-control input-sm" name="editarTelefono" id="editarTelefono" placeholder="Telefono - 1">

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL TELEFONO 1 -->

                            <div class="form-group col-lg-2">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <input type="text" class="form-control input-sm" name="editarTelefono2" id="editarTelefono2" placeholder="Telefono - 2">

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL E-MAIL -->

                            <div class="form-group col-lg-4">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <input type="text" class="form-control input-sm" name="editarEmail" id="editarEmail" placeholder="E - mail">

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL CONTACTO -->

                            <div class="form-group col-lg-4">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <input type="text" class="form-control input-sm" name="editarContacto" id="editarContacto" placeholder="Contacto">

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL VENDEDOR -->

                            <div class="form-group col-lg-4">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <select class="form-control input-sm selectpicker" id="editarVendedor" name="editarVendedor" data-live-search="true" data-size="5">

                                        <option value="">Seleccionar Vendedor</option>
                                        <?php
                                        $item = null;
                                        $valor = null;

                                        $vendedor = ControladorVendedores::ctrMostrarVendedores($item, $valor);
                                        foreach ($vendedor as $key => $value) {
                                            echo "<option value='" . $value["codigo"] . "'>" . $value["codigo"] . " - " . $value["descripcion"] . "</option>";
                                        }
                                        ?>

                                    </select>

                                </div>

                            </div>

                            <!-- ENTRADA PARA LOS GRUPOS -->

                            <div class="form-group col-lg-2">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <select class="form-control input-sm" id="editarGrupo" name="editarGrupo">

                                        <option value="">Grupo</option>
                                        <?php foreach ($gruposEmpresariales as $grupoItem) : ?>
                                            <option value="<?php echo htmlspecialchars($grupoItem["codigo"]); ?>">
                                                <?php echo htmlspecialchars($grupoItem["nombre"]); ?>
                                            </option>
                                        <?php endforeach; ?>

                                    </select>

                                </div>

                            </div>

                            <!-- ENTRADA PARA LA LISTA DE PRECIOS -->

                            <div class="form-group col-lg-2">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <select class="form-control input-sm selectpicker" id="editarLista_precios" name="editarLista_precios" required>

                                        <option value="">Lista de Precios</option>
                                        <option value="precio1">Lista - 01</option>
                                        <option value="precio2">Lista - 02</option>
                                        <option value="precio3">Lista - 03</option>
                                        <option value="precio4">Lista - 04</option>
                                        <option value="precio5">Lista - 05</option>
                                        <option value="precio6">Lista - 06</option>
                                        <option value="precio7">Lista - 07</option>
                                        <option value="precio8">Lista - 08</option>
                                        <option value="precio9">Lista - 09</option>
                                        <option value="precio10">Lista - 10</option>
                                        <option value="precio11">Lista - 11</option>


                                    </select>

                                </div>

                            </div>

                            <!-- ENTRADA PARA AGENCIA DE TRANSPORTES-->

                            <div class="form-group col-lg-4">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                    <select class="form-control selectpicker" id="editarAgencia" name="editarAgencia" data-live-search="true">

                                        <option value="">Agencia de Transportes</option>
                                        <?php

                                        $agencia = ControladorAgencias::ctrMostrarAgencias(null, null);

                                        //var_dump($agencia);

                                        foreach ($agencia as $key => $value) {

                                            echo '<option value="' . $value["id"] . '">' . $value["id"] . ' - ' . $value["nombre"] . '</option>';
                                        }

                                        ?>

                                    </select>

                                </div>

                            </div>

                            <!-- CATEGORÍA COMERCIAL (EDITAR) -->
                            <div class="form-group col-lg-2" id="bloqueCategoriaComercialEditar">
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-tags"></i></span>
                                    <select class="form-control input-sm selectpicker" id="categoriaComercialEditar" name="editar_categoria_comercial" data-live-search="true" title="Categoría comercial">
                                        <option value="">Categoría comercial</option>
                                        <?php foreach ($categoriasComercialesActivas as $catItem) : ?>
                                            <option value="<?php echo (int) $catItem["id"]; ?>">
                                                <?php echo htmlspecialchars($catItem["nombre"]); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>



                        </div>

                        <!-- FIN DATOS DIRECCION -->



                    </div>

                </div>

                <!--=====================================
        PIE DEL MODAL
        ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" class="btn btn-primary">Guardar cliente</button>

                </div>

            </form>

            <?php

            $editarCliente = new ControladorClientes();
            $editarCliente->ctrEditarCliente();

            ?>

        </div>

    </div>

</div>


<!--=====================================
MODAL EDITAR AVAL DE CLIENTE
======================================-->

<div id="modalEditarAval" class="modal fade" role="dialog">

    <div class="modal-dialog">

        <div class="modal-content">

            <form role="form" method="post">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Editar Aval</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">


                        <!-- ENTRADA PARA EL CODIGO  -->

                        <div class="form-group">
                            <label for="">Nombre</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-user"></i></span>

                                <input type="text" class="form-control input-lg" name="editarAvalNombre" id="editarAvalNombre" required>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group">
                            <label for="">Dirección</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-map"></i></span>

                                <input type="text" class="form-control input-lg" style="text-transform:uppercase" name="editarAvalDir" id="editarAvalDir" required>
                                <input type="hidden" id="avalCliente" name="avalCliente">
                            </div>

                        </div>

                        <div class="form-group">
                            <label for="">Cod. postal</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-map-marker"></i></span>

                                <select class="form-control input-lg selectpicker" data-live-search="true" name="editarAvalPostal" id="editarAvalPostal" data-size="10" required>
                                    <option value="">Seleccionar codigo postal</option>
                                    <?php

                                    $ubigeo = ControladorClientes::ctrMostrarUbigeos();
                                    #var_dump("ubigeo", $ubigeo);

                                    foreach ($ubigeo as $key => $value) {

                                        echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . ' - ' . $value["ubigeo"] . '</option>';
                                    }


                                    ?>

                                </select>

                            </div>

                        </div>

                        <div class="form-group">
                            <label for="">Teléfono</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-phone"></i></span>

                                <input type="text" class="form-control input-lg" name="editarAvalTelf" id="editarAvalTelf">

                            </div>

                        </div>

                        <div class="form-group">
                            <label for="">RUC</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                <input type="number" class="form-control input-lg" name="editarAvalRuc" id="editarAvalRuc" required>

                            </div>

                        </div>

                        <div class="form-group">
                            <label for="">DNI</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-id-card-o"></i></span>

                                <input type="number" class="form-control input-lg" name="editarAvalLibreta" id="editarAvalLibreta">

                            </div>

                        </div>

                    </div>

                </div>

                <!--=====================================
        PIE DEL MODAL
        ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" class="btn btn-primary">Guardar Aval</button>

                </div>

            </form>

            <?php

            $editarAval = new ControladorClientes();
            $editarAval->ctrEditarAval();

            ?>


        </div>

    </div>

</div>

<style>
    table.tablaClientes td.clientes-col-acciones,
    table.tablaClientes th.clientes-col-acciones {
        white-space: nowrap !important;
        width: 120px !important;
        min-width: 120px !important;
        text-align: center !important;
    }

    table.tablaClientes .clientesAccionesWrap {
        display: inline-block !important;
        white-space: nowrap !important;
    }

    table.tablaClientes .clientesAccionesWrap > .btn {
        float: none !important;
        display: inline-block !important;
        margin: 0 1px 0 0 !important;
    }
</style>

<script>
    window.document.title = "Clientes"
</script>