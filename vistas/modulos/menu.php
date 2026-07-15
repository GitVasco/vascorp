<aside class="main-sidebar">

    <section class="sidebar">

        <ul class="sidebar-menu">

            <?php
            $item = "idusuario";
            $valor = $_SESSION["id"];
            $permisos = ControladorUsuarios::ctrMostrarUsuariosPermisos($item, $valor);
            $valores = array();
            foreach ($permisos as $key => $value) {
                array_push($valores, $value["idpermiso"]);
            }
            in_array(1, $valores) ? $_SESSION['escritorio'] = 1 : $_SESSION['escritorio'] = 0;
            in_array(2, $valores) ? $_SESSION['analisis'] = 1 : $_SESSION['analisis'] = 0;
            in_array(3, $valores) ? $_SESSION['usuarios'] = 1 : $_SESSION['usuarios'] = 0;
            in_array(4, $valores) ? $_SESSION['backend'] = 1 : $_SESSION['backend'] = 0;
            in_array(5, $valores) ? $_SESSION['movimientos'] = 1 : $_SESSION['movimientos'] = 0;
            in_array(6, $valores) ? $_SESSION['maestros'] = 1 : $_SESSION['maestros'] = 0;
            in_array(7, $valores) ? $_SESSION['produccion'] = 1 : $_SESSION['produccion'] = 0;
            in_array(8, $valores) ? $_SESSION['tarjetas'] = 1 : $_SESSION['tarjetas'] = 0;
            in_array(9, $valores) ? $_SESSION['operaciones'] = 1 : $_SESSION['operaciones'] = 0;
            in_array(10, $valores) ? $_SESSION['materiaprima'] = 1 : $_SESSION['materiaprima'] = 0;
            in_array(11, $valores) ? $_SESSION['ventas'] = 1 : $_SESSION['ventas'] = 0;
            in_array(12, $valores) ? $_SESSION['facturacion'] = 1 : $_SESSION['facturacion'] = 0;
            in_array(13, $valores) ? $_SESSION['ticket'] = 1 : $_SESSION['ticket'] = 0;
            in_array(14, $valores) ? $_SESSION['cuenta'] = 1 : $_SESSION['cuenta'] = 0;
            in_array(15, $valores) ? $_SESSION['costos'] = 1 : $_SESSION['costos'] = 0;
            in_array(16, $valores) ? $_SESSION['caja'] = 1 : $_SESSION['caja'] = 0;
            in_array(17, $valores) ? $_SESSION['mantenimiento'] = 1 : $_SESSION['mantenimiento'] = 0;
            ?>

            <!-- search form -->
            <div class="input-group sidebar-form">
                <input type="text" name="q" class="form-control search-menu-box" placeholder="Buscar...">
            </div>

            <!-- Escritorio -->
            <?php
            if ($_SESSION["escritorio"] == 1) {
            ?>

                <li class="<?php if ($_GET["ruta"] == "inicio") echo 'active'; ?>">

                    <a href="inicio">

                        <i class="fa fa-home"></i>
                        <span>Inicio</span>

                    </a>

                </li>

            <?php
            }
            ?>

            <!--  Analisis-->
            <?php
            if ($_SESSION["analisis"] == 1) {
            ?>

                <li class="<?php if ($_GET["ruta"] == "inicio-gerencia") echo 'active'; ?>">

                    <a href="inicio-gerencia">

                        <i class="fa fa-globe"></i>
                        <span>Analisis</span>

                    </a>

                </li>

            <?php
            }
            ?>

            <!-- Gestión comercial -->
            <?php
            $puedeVerDashboardCobranzas = function_exists("usuarioPuedeVerModulo")
                && usuarioPuedeVerModulo("gestion_comercial", "dashboard_cobranzas");
            $puedeVerCentroDecisiones = function_exists("usuarioPuedeVerModulo")
                && usuarioPuedeVerModulo("gestion_comercial", "centro_decisiones");
            $puedeVerHistorialCredito = function_exists("usuarioPuedeModulo")
                && usuarioPuedeModulo("gestion_comercial", "centro_decisiones", "historial");
            $puedeVerMetasVendedor = function_exists("usuarioPuedeVerModulo")
                && usuarioPuedeVerModulo("gestion_comercial", "metas_vendedor");
            $puedeVerLineaCredito = function_exists("usuarioPuedeVerModulo")
                && usuarioPuedeVerModulo("gestion_comercial", "linea_credito");
            $puedeVerInteligenciaComercial = function_exists("usuarioPuedeVerModulo")
                && usuarioPuedeVerModulo("gestion_comercial", "inteligencia_comercial");
            $puedeVerCategoriasComerciales = function_exists("usuarioPuedeVerModulo")
                && usuarioPuedeVerModulo("gestion_comercial", "categorias_comerciales");
            $puedeVerCategoriasPorRevisar = function_exists("usuarioPuedeVerModulo")
                && usuarioPuedeVerModulo("gestion_comercial", "categorias_por_revisar");
            $puedeVerZonasComerciales = function_exists("usuarioPuedeVerModulo")
                && usuarioPuedeVerModulo("gestion_comercial", "zonas_comerciales");
            $puedeVerGruposMarcas = function_exists("usuarioPuedeVerModulo")
                && usuarioPuedeVerModulo("gestion_comercial", "grupos_marcas");
            $puedeVerAsignacionGruposMarcas = function_exists("usuarioPuedeVerModulo")
                && usuarioPuedeVerModulo("gestion_comercial", "asignacion_grupos_marcas");

            $mostrarCreditoCobranzas = $puedeVerDashboardCobranzas
                || $puedeVerCentroDecisiones
                || $puedeVerHistorialCredito
                || $puedeVerLineaCredito;
            $mostrarMetasInteligencia = $puedeVerInteligenciaComercial || $puedeVerMetasVendedor;
            $mostrarCatalogosComerciales = $puedeVerCategoriasComerciales
                || $puedeVerCategoriasPorRevisar
                || $puedeVerZonasComerciales
                || $puedeVerGruposMarcas
                || $puedeVerAsignacionGruposMarcas;

            $rutasActivasCreditoCobranzas = array();
            if ($puedeVerDashboardCobranzas) {
                $rutasActivasCreditoCobranzas[] = "dashboard-cobranzas";
            }
            if ($puedeVerCentroDecisiones) {
                $rutasActivasCreditoCobranzas[] = "dashboard-decisiones";
            }
            if ($puedeVerHistorialCredito) {
                $rutasActivasCreditoCobranzas[] = "historial-credito";
            }
            if ($puedeVerLineaCredito) {
                $rutasActivasCreditoCobranzas[] = "linea-credito";
            }

            $rutasActivasMetasInteligencia = array();
            if ($puedeVerInteligenciaComercial) {
                $rutasActivasMetasInteligencia[] = "inteligencia-comercial";
            }
            if ($puedeVerMetasVendedor) {
                $rutasActivasMetasInteligencia[] = "metas-vendedor";
                $rutasActivasMetasInteligencia[] = "metas-retos";
            }

            $rutasActivasCatalogosComerciales = array();
            if ($puedeVerCategoriasComerciales) {
                $rutasActivasCatalogosComerciales[] = "categorias-comerciales";
            }
            if ($puedeVerCategoriasPorRevisar) {
                $rutasActivasCatalogosComerciales[] = "categorias-por-revisar";
            }
            if ($puedeVerZonasComerciales) {
                $rutasActivasCatalogosComerciales[] = "zonas-comerciales";
                $rutasActivasCatalogosComerciales[] = "mapas-zonas";
            }
            if ($puedeVerGruposMarcas) {
                $rutasActivasCatalogosComerciales[] = "grupos-marcas";
            }
            if ($puedeVerAsignacionGruposMarcas) {
                $rutasActivasCatalogosComerciales[] = "asignacion-grupos-marcas";
            }

            $rutasActivasGestionComercial = array_merge(
                $rutasActivasCreditoCobranzas,
                $rutasActivasMetasInteligencia,
                $rutasActivasCatalogosComerciales
            );
            $mostrarGestionComercial = !empty($rutasActivasGestionComercial);

            if ($mostrarGestionComercial) {
                $isActiveGestionComercial = in_array($_GET["ruta"], $rutasActivasGestionComercial, true) ? "active" : "";
                $isActiveCreditoCobranzas = in_array($_GET["ruta"], $rutasActivasCreditoCobranzas, true) ? "active" : "";
                $isActiveMetasInteligencia = in_array($_GET["ruta"], $rutasActivasMetasInteligencia, true) ? "active" : "";
                $isActiveCatalogosComerciales = in_array($_GET["ruta"], $rutasActivasCatalogosComerciales, true) ? "active" : "";
            ?>

                <li class="treeview <?php echo $isActiveGestionComercial; ?>">

                    <a href="#">

                        <i class="fa fa-briefcase"></i>

                        <span>Gestión comercial</span>

                        <span class="pull-right-container">

                            <i class="fa fa-angle-left pull-right"></i>

                        </span>

                    </a>

                    <ul class="treeview-menu">

                        <?php if ($mostrarCreditoCobranzas) { ?>
                        <li class="treeview <?php echo $isActiveCreditoCobranzas; ?>">
                            <a href="#">
                                <i class="fa fa-money"></i>
                                <span>Crédito y cobranzas</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <?php if ($puedeVerDashboardCobranzas) { ?>
                                <li class="<?php if ($_GET["ruta"] == "dashboard-cobranzas") echo 'active'; ?>">
                                    <a href="index.php?ruta=dashboard-cobranzas">
                                        <i class="fa fa-dashboard"></i>
                                        <span>Dashboard cobranzas</span>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if ($puedeVerCentroDecisiones) { ?>
                                <li class="<?php if ($_GET["ruta"] == "dashboard-decisiones") echo 'active'; ?>">
                                    <a href="index.php?ruta=dashboard-decisiones">
                                        <i class="fa fa-gavel"></i>
                                        <span>Centro de decisiones</span>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if ($puedeVerHistorialCredito) { ?>
                                <li class="<?php if ($_GET["ruta"] == "historial-credito") echo 'active'; ?>">
                                    <a href="index.php?ruta=historial-credito">
                                        <i class="fa fa-history"></i>
                                        <span>Historial de crédito</span>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if ($puedeVerLineaCredito) { ?>
                                <li class="<?php if ($_GET["ruta"] == "linea-credito") echo 'active'; ?>">
                                    <a href="index.php?ruta=linea-credito">
                                        <i class="fa fa-credit-card"></i>
                                        <span>Línea de crédito</span>
                                    </a>
                                </li>
                                <?php } ?>
                            </ul>
                        </li>
                        <?php } ?>

                        <?php if ($mostrarMetasInteligencia) { ?>
                        <li class="treeview <?php echo $isActiveMetasInteligencia; ?>">
                            <a href="#">
                                <i class="fa fa-line-chart"></i>
                                <span>Metas e inteligencia</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <?php if ($puedeVerInteligenciaComercial) { ?>
                                <li class="<?php if ($_GET["ruta"] == "inteligencia-comercial") echo 'active'; ?>">
                                    <a href="index.php?ruta=inteligencia-comercial">
                                        <i class="fa fa-lightbulb-o"></i>
                                        <span>Inteligencia comercial</span>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if ($puedeVerMetasVendedor) { ?>
                                <li class="<?php if ($_GET["ruta"] == "metas-vendedor") echo 'active'; ?>">
                                    <a href="index.php?ruta=metas-vendedor">
                                        <i class="fa fa-bullseye"></i>
                                        <span>Metas vendedor</span>
                                    </a>
                                </li>
                                <li class="<?php if ($_GET["ruta"] == "metas-retos") echo 'active'; ?>">
                                    <a href="index.php?ruta=metas-retos">
                                        <i class="fa fa-trophy"></i>
                                        <span>Metas / retos</span>
                                    </a>
                                </li>
                                <?php } ?>
                            </ul>
                        </li>
                        <?php } ?>

                        <?php if ($mostrarCatalogosComerciales) { ?>
                        <li class="treeview <?php echo $isActiveCatalogosComerciales; ?>">
                            <a href="#">
                                <i class="fa fa-map-o"></i>
                                <span>Catálogos comerciales</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <?php if ($puedeVerCategoriasComerciales) { ?>
                                <li class="<?php if ($_GET["ruta"] == "categorias-comerciales") echo 'active'; ?>">
                                    <a href="categorias-comerciales">
                                        <i class="fa fa-tags"></i>
                                        <span>Categorías comerciales</span>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if ($puedeVerCategoriasPorRevisar) { ?>
                                <li class="<?php if ($_GET["ruta"] == "categorias-por-revisar") echo 'active'; ?>">
                                    <a href="categorias-por-revisar">
                                        <i class="fa fa-flag"></i>
                                        <span>Categorías por revisar</span>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if ($puedeVerZonasComerciales) { ?>
                                <li class="<?php if ($_GET["ruta"] == "zonas-comerciales") echo 'active'; ?>">
                                    <a href="index.php?ruta=zonas-comerciales">
                                        <i class="fa fa-map"></i>
                                        <span>Zonas comerciales</span>
                                    </a>
                                </li>
                                <li class="<?php if ($_GET["ruta"] == "mapas-zonas") echo 'active'; ?>">
                                    <a href="index.php?ruta=mapas-zonas">
                                        <i class="fa fa-globe"></i>
                                        <span>Mapas de zonas</span>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if ($puedeVerGruposMarcas) { ?>
                                <li class="<?php if ($_GET["ruta"] == "grupos-marcas") echo 'active'; ?>">
                                    <a href="index.php?ruta=grupos-marcas">
                                        <i class="fa fa-object-group"></i>
                                        <span>Grupos de marcas</span>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if ($puedeVerAsignacionGruposMarcas) { ?>
                                <li class="<?php if ($_GET["ruta"] == "asignacion-grupos-marcas") echo 'active'; ?>">
                                    <a href="index.php?ruta=asignacion-grupos-marcas">
                                        <i class="fa fa-handshake-o"></i>
                                        <span>Asignación de grupos</span>
                                    </a>
                                </li>
                                <?php } ?>
                            </ul>
                        </li>
                        <?php } ?>

                    </ul>

                </li>

            <?php
            }
            ?>


            <!--  Usuarios-->
            <?php
            if ($_SESSION["usuarios"] == 1) {
            ?>

                <li class="<?php if ($_GET["ruta"] == "usuarios") echo 'active'; ?>">

                    <a href="usuarios">

                        <i class="fa fa-user"></i>
                        <span>Usuarios</span>

                    </a>

                </li>

            <?php
            }
            ?>

            <!--  Backend-->
            <?php
            if ($_SESSION["backend"] == 1) {
            ?>

                <li class="treeview <?php if (
                                        $_GET["ruta"] == "movimientos" ||
                                        $_GET["ruta"] == "datos-dia" ||
                                        $_GET["ruta"] == "backupDB" ||
                                        $_GET["ruta"] == "bkplista" ||
                                        $_GET["ruta"] == "cargas-automaticas" ||
                                        $_GET["ruta"] == "conexionjf"
                                    ) echo 'active'; ?>">

                    <a href="#">

                        <i class="fa fa-code"></i>

                        <span>Backend</span>

                        <span class="pull-right-container">

                            <i class="fa fa-angle-left pull-right"></i>

                        </span>

                    </a>

                    <ul class="treeview-menu">

                        <li class="<?php if ($_GET["ruta"] == "movimientos") echo 'active'; ?>">

                            <a href="movimientos">

                                <i class="fa fa-circle-o"></i>
                                <span>Movimientos</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "datos-dia") echo 'active'; ?>">

                            <a href="datos-dia">

                                <i class="fa fa-circle-o"></i>
                                <span>Datos Diarios</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "backupDB") echo 'active'; ?>">

                            <a href="#">

                                <i class="fa fa-circle-o"></i>
                                <span>Backup</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "bkplista") echo 'active'; ?>">

                            <a href="bkplista">

                                <i class="fa fa-circle-o"></i>
                                <span>Backup - Listos</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "cargas-automaticas") echo 'active'; ?>">

                            <a href="cargas-automaticas">

                                <i class="fa fa-circle-o"></i>
                                <span>Cargas automaticas</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "conexionjf") echo 'active'; ?>">

                            <a href="conexionjf">

                                <i class="fa fa-circle-o"></i>
                                <span>Conexiones</span>

                            </a>

                        </li>

                    </ul>

                </li>

            <?php
            }
            ?>

            <!--  Movimientos-->
            <?php
            if ($_SESSION["movimientos"] == 1) {
            ?>

                <li class="treeview <?php if (
                                        $_GET["ruta"] == "m-produccion" ||
                                        $_GET["ruta"] == "m-ventas" ||
                                        $_GET["ruta"] == "mp-ingresos" ||
                                        $_GET["ruta"] == "mp-salidas"
                                    ) echo 'active'; ?>">

                    <a href="#">

                        <i class="fa fa-line-chart text-info"></i>

                        <span>Movimientos</span>

                        <span class="pull-right-container">

                            <i class="fa fa-angle-left pull-right"></i>

                        </span>

                    </a>

                    <ul class="treeview-menu">

                        <li class="<?php if ($_GET["ruta"] == "m-produccion") echo 'active'; ?>">

                            <a href="m-produccion">

                                <i class="fa fa-circle-o"></i>
                                <span>Produccion</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "m-ventas") echo 'active'; ?>">

                            <a href="m-ventas">

                                <i class="fa fa-circle-o"></i>
                                <span>Ventas</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "mp-ingresos") echo 'active'; ?>">

                            <a href="mp-ingresos">

                                <i class="fa fa-circle-o"></i>
                                <span>Ingresos MP</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "mp-salidas") echo 'active'; ?>">

                            <a href="mp-salidas">

                                <i class="fa fa-circle-o"></i>
                                <span>Salidas MP</span>

                            </a>

                        </li>

                    </ul>

                </li>

            <?php
            }
            ?>

            <?php
            if ($_SESSION["maestros"] == 1) {
            ?>

                <li class="treeview <?php if (
                                        $_GET["ruta"] == "articulos" ||
                                        $_GET["ruta"] == "crear-articulo" ||
                                        $_GET["ruta"] == "agencias" ||
                                        $_GET["ruta"] == "bancos" ||
                                        $_GET["ruta"] == "colores" ||
                                        $_GET["ruta"] == "condicionesventa" ||
                                        $_GET["ruta"] == "tipodocumentos" ||
                                        $_GET["ruta"] == "marcas" ||
                                        $_GET["ruta"] == "modelosjf" ||
                                        $_GET["ruta"] == "operaciones" ||
                                        $_GET["ruta"] == "paras" ||
                                        $_GET["ruta"] == "sectores" ||
                                        $_GET["ruta"] == "marcas" ||
                                        $_GET["ruta"] == "tipomovimientos" ||
                                        $_GET["ruta"] == "tipopagos" ||
                                        $_GET["ruta"] == "tipotrabajador" ||
                                        $_GET["ruta"] == "trabajador" ||
                                        $_GET["ruta"] == "trabajador2" ||
                                        $_GET["ruta"] == "unidadesmedida" ||
                                        $_GET["ruta"] == "vendedor"
                                    ) echo 'active'; ?>">

                    <a href="#">

                        <i class="fa fa-database text-red"></i>

                        <span>Maestros</span>

                        <span class="pull-right-container">

                            <i class="fa fa-angle-left pull-right"></i>

                        </span>

                    </a>

                    <ul class="treeview-menu">

                        <li class="<?php if ($_GET["ruta"] == "articulos") echo 'active'; ?>">

                            <a href="articulos">

                                <i class="fa fa-circle-o"></i>
                                <span>Artículos</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "agencias") echo 'active'; ?>">
                            <a href="agencias">

                                <i class="fa fa-circle-o"></i>
                                <span>Agencias</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "bancos") echo 'active'; ?>">
                            <a href="bancos">

                                <i class="fa fa-circle-o"></i>
                                <span>Bancos</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "colores") echo 'active'; ?>">

                            <a href="colores">

                                <i class="fa fa-circle-o"></i>
                                <span>Colores</span>

                            </a>

                        </li>


                        <li class="<?php if ($_GET["ruta"] == "condicionesventa") echo 'active'; ?>">
                            <a href="condicionesventa">

                                <i class="fa fa-circle-o"></i>
                                <span>Condiciones Venta</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "tipodocumentos") echo 'active'; ?>">
                            <a href="tipodocumentos">

                                <i class="fa fa-circle-o"></i>
                                <span>Documentos</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "marcas") echo 'active'; ?>">

                            <a href="marcas">

                                <i class="fa fa-circle-o"></i>
                                <span>Marcas</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "modelosjf") echo 'active'; ?>">

                            <a href="modelosjf">

                                <i class="fa fa-circle-o"></i>
                                <span>Modelos</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "operaciones") echo 'active'; ?>">

                            <a href="operaciones">

                                <i class="fa fa-circle-o"></i>
                                <span>Operaciones</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "paras") echo 'active'; ?>">
                            <a href="paras">

                                <i class="fa fa-circle-o"></i>
                                <span>Paras</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "sectores") echo 'active'; ?>">
                            <a href="sectores">

                                <i class="fa fa-circle-o"></i>
                                <span>Sector</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "tipomovimientos") echo 'active'; ?>">
                            <a href="tipomovimientos">

                                <i class="fa fa-circle-o"></i>
                                <span>Tipo Movimientos</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "tipopagos") echo 'active'; ?>">
                            <a href="tipopagos">

                                <i class="fa fa-circle-o"></i>
                                <span>Tipo Pagos</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "tipotrabajador") echo 'active'; ?>">
                            <a href="tipotrabajador">

                                <i class="fa fa-circle-o"></i>
                                <span>Tipo Trabajador</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "trabajador") echo 'active'; ?>">
                            <a href="trabajador">

                                <i class="fa fa-circle-o"></i>
                                <span>Trabajador</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "trabajador2") echo 'active'; ?>">
                            <a href="trabajador2">

                                <i class="fa fa-circle-o"></i>
                                <span>Trabajador 2</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "unidadesmedida") echo 'active'; ?>">
                            <a href="unidadesmedida">

                                <i class="fa fa-circle-o"></i>
                                <span>Unidades Medida</span>

                            </a>

                        </li>



                        <li class="<?php if ($_GET["ruta"] == "vendedor") echo 'active'; ?>">
                            <a href="vendedor">

                                <i class="fa fa-circle-o"></i>
                                <span>Vendedor</span>

                            </a>

                        </li>
                    </ul>

                </li>

            <?php
            }
            ?>

            <!-- Produccion VERSION NUEVA-->
            <?php if ($_SESSION["produccion"] == 1) : ?>

                <?php
                $rutasActivasProduccion = [
                    "ordencorte", "crear-ordencorte", "editar-detalle-ordencorte", "editar-almacencorte-lote", "editar-consumo", "almacencorte",
                    "crear-almacencorte", "en-cortes", "en-taller", "marcar-taller", "en-tallert",
                    "en-tallerp", "ingresos", "crear-ingresos", "crear-ingresos-multi", "crear-segunda", "crear-segundas-multi", "asistencia",
                    "quincena", "eficiencia-global", "produccion-trusas", "produccion-brasier",
                    "produccion-vasco", "urgencias", "urgenciasamp", "proyeccion-mp", "servicios",
                    "crear-servicio", "cierres", "crear-cierre", "precio-servicio", "pago-servicio",
                    "salidas-varios", "crear-salidas-varios", "operacion-taller", "sublimados",
                    "seguimiento", "enviados-taller", "listar-documento", "ajuste-taller",
                    "urgencias-produccion", "urgencias-almacen", "urgencias-corte", "urgencias-plan",
                    "urgencias-maestro", "transferencias-apt", "crear-transferencias-apt", "estampado", "tampografia", "prehormado", "arreglos", "crear-arreglos", "cerrar-arreglos", "en-talleres"
                ];

                $isActiveProduccion = in_array($_GET["ruta"], $rutasActivasProduccion) ? 'active' : '';
                ?>
                <li class="treeview <?= $isActiveProduccion; ?>">

                    <a href="#">
                        <i class="fa fa-cogs"></i> <span>Producción <label class="text-danger">TEST</label></span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">

                        <?php
                        $rutasActivasProgramación = ["ordencorte", "almacencorte", "servicios", "cierres", "ingresos"];
                        $isActiveProgramación = in_array($_GET["ruta"], $rutasActivasProgramación) ? 'active' : '';
                        ?>

                        <!-- PROGRAMACIÓN -->
                        <li class="treeview <?= $isActiveProgramación; ?>">
                            <a href=" #"><i class="fa fa-circle-o"></i> Programación
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">

                                <!-- ORDEN DE CORTE -->
                                <li class="<?= $_GET['ruta'] == 'ordencorte' ? 'active' : '' ?>">
                                    <a href="ordencorte"><i class="fa fa-circle-o"></i> Ord. Corte</a>
                                </li>

                                <!-- CORTES -->
                                <li class="<?= $_GET['ruta'] == 'almacencorte' ? 'active' : '' ?>">
                                    <a href="almacencorte"><i class="fa fa-circle-o"></i> Cortes</a>
                                </li>

                                <!-- SERVICIOS -->
                                <li class="<?= $_GET['ruta'] == 'servicios' ? 'active' : '' ?>">
                                    <a href="servicios"><i class="fa fa-circle-o"></i> Servicios</a>
                                </li>

                                <!-- CIERRES -->
                                <li class="<?= $_GET['ruta'] == 'cierres' ? 'active' : '' ?>">
                                    <a href="cierres"><i class="fa fa-circle-o"></i> Cierres</a>
                                </li>

                                <!-- ARREGLOS -->
                                <li class="<?= $_GET['ruta'] == 'arreglos' ? 'active' : '' ?>">
                                    <a href="arreglos"><i class="fa fa-circle-o"></i> Arreglos</a>
                                </li>

                                <!-- INGRESOS -->
                                <li class="<?= $_GET['ruta'] == 'ingresos' ? 'active' : '' ?>">
                                    <a href="ingresos"><i class="fa fa-circle-o"></i> Ingresos</a>
                                </li>

                            </ul>
                        </li>

                        <!-- TALLER VASCO -->
                        <?php
                        $rutasActivasTallerVasco = ["en-taller", "marcar-taller", "en-tallert", "en-tallerp", "quincena"];
                        $isActiveTallerVasco = in_array($_GET["ruta"], $rutasActivasTallerVasco) ? 'active' : '';
                        ?>
                        <li class="treeview <?= $isActiveTallerVasco; ?>">
                            <a href=" #"><i class="fa fa-circle-o"></i> Taller Vasco
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li class="<?= $_GET['ruta'] == 'en-taller' ? 'active' : '' ?>">
                                    <a href="en-taller"><i class="fa fa-circle-o"></i> Taller Gral</a>
                                </li>
                                <li class="<?= $_GET['ruta'] == 'marcar-taller' ? 'active' : '' ?>">
                                    <a href="marcar-taller"><i class="fa fa-circle-o"></i> Registrar</a>
                                </li>
                                <li class="<?= $_GET['ruta'] == 'en-tallerp' ? 'active' : '' ?>">
                                    <a href="en-tallerp"><i class="fa fa-circle-o"></i> Generados</a>
                                </li>
                                <li class="<?= $_GET['ruta'] == 'en-tallert' ? 'active' : '' ?>">
                                    <a href="en-tallert"><i class="fa fa-circle-o"></i> Terminados</a>
                                </li>
                                <li class="<?= $_GET['ruta'] == 'quincena' ? 'active' : '' ?>">
                                    <a href="quincena"><i class="fa fa-circle-o"></i> Quincenas</a>
                                </li>

                            </ul>
                        </li>

                        <!-- GASTOS -->
                        <?php
                        $rutasActivasGastos = ["precio-servicio", "pago-servicio"];
                        $isActiveGastos = in_array($_GET["ruta"], $rutasActivasGastos) ? 'active' : '';
                        ?>
                        <li class="treeview <?= $isActiveGastos; ?>">
                            <a href=" #"><i class="fa fa-circle-o"></i> Gastos
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li class="<?= $_GET['ruta'] == 'precio-servicio' ? 'active' : '' ?>">
                                    <a href="precio-servicio"><i class="fa fa-circle-o"></i> Precios</a>
                                </li>
                                <li class="<?= $_GET['ruta'] == 'pago-servicio' ? 'active' : '' ?>">
                                    <a href="pago-servicio"><i class="fa fa-circle-o"></i> Pagos</a>
                                </li>
                            </ul>
                        </li>

                        <!-- RESUMEN -->
                        <?php
                        $rutasActivasResumen = ["en-cortes", "enviados-taller"];
                        $isActiveResumen = in_array($_GET["ruta"], $rutasActivasResumen) ? 'active' : '';
                        ?>
                        <li class="treeview <?= $isActiveResumen; ?>">
                            <a href="#"><i class="fa fa-circle-o"></i> Resumen
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li class="<?= $_GET['ruta'] == 'en-cortes' ? 'active' : '' ?>">
                                    <a href="en-cortes"><i class="fa fa-circle-o"></i> Alm. Corte</a>
                                </li>
                                <li class="<?= $_GET['ruta'] == 'enviados-taller' ? 'active' : '' ?>">
                                    <a href="enviados-taller"><i class="fa fa-circle-o"></i> Env. Taller</a>
                                </li>

                            </ul>
                        </li>

                        <!-- CONTROLES -->
                        <?php
                        $rutasActivasControles = ["estampado", "salidas-varios", "listar-documento", "transferencias-apt", "prehormado"];
                        $isActiveControles = in_array($_GET["ruta"], $rutasActivasControles) ? 'active' : '';
                        ?>
                        <li class="treeview <?= $isActiveControles; ?>">
                            <a href="#"><i class="fa fa-circle-o"></i> Controles
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li class="<?= $_GET['ruta'] == 'estampado' ? 'active' : '' ?>">
                                    <a href="estampado"><i class="fa fa-circle-o"></i> Estampados</a>
                                </li>
                                <li class="<?= $_GET['ruta'] == 'tampografia' ? 'active' : '' ?>">
                                    <a href="tampografia"><i class="fa fa-circle-o"></i> Tampografia</a>
                                </li>
                                <li class="<?= $_GET['ruta'] == 'prehormado' ? 'active' : '' ?>">
                                    <a href="prehormado"><i class="fa fa-circle-o"></i> Prehormado</a>
                                </li>
                                <?php
                                $rutasActivasMovimientos = ["salidas-varios", "listar-documento"];
                                $isActiveMovimientos = in_array($_GET["ruta"], $rutasActivasMovimientos) ? 'active' : '';
                                ?>
                                <li class="treeview <?= $isActiveMovimientos; ?>">
                                    <a href="#"><i class="fa fa-circle-o"></i> Movimientos
                                        <span class="pull-right-container">
                                            <i class="fa fa-angle-left pull-right"></i>
                                        </span>
                                    </a>
                                    <ul class="treeview-menu">
                                        <li class="<?= $_GET['ruta'] == 'salidas-varios' ? 'active' : '' ?>">
                                            <a href="salidas-varios"><i class="fa fa-circle-o"></i> Registros</a>
                                        </li>
                                        <li class="<?= $_GET['ruta'] == 'listar-documento' ? 'active' : '' ?>">
                                            <a href="listar-documento"><i class="fa fa-circle-o"></i> Documentos</a>
                                        </li>
                                    </ul>
                                </li>
                                <li class="<?= $_GET['ruta'] == 'transferencias-apt' ? 'active' : '' ?>">
                                    <a href="transferencias-apt"><i class="fa fa-circle-o"></i> Transferencias</a>
                                </li>
                            </ul>
                        </li>

                        <!-- REPORTES -->
                        <?php
                        $rutasActivasResumen = ["seguimiento", 'urgencias', 'urgencias-maestro', 'urgencias-produccion', 'urgencias-almacen', 'urgencias-corte', 'urgencias-plan', 'en-talleres'];
                        $isActiveResumen = in_array($_GET["ruta"], $rutasActivasResumen) ? 'active' : '';
                        ?>
                        <li class="treeview <?= $isActiveResumen; ?>">
                            <a href="#"><i class="fa fa-circle-o"></i> Reportes
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li class="<?= $_GET['ruta'] == 'seguimiento' ? 'active' : '' ?>">
                                    <a href="seguimiento"><i class="fa fa-circle-o"></i> Seguimiento</a>
                                </li>
                                <li class="<?= $_GET['ruta'] == 'en-talleres' ? 'active' : '' ?>">
                                    <a href="en-talleres"><i class="fa fa-circle-o"></i> En Talleres</a>
                                </li>
                                <?php
                                $rutasActivasUrgencia = ['urgencias', 'urgencias-maestro', 'urgencias-produccion', 'urgencias-almacen', 'urgencias-corte', 'urgencias-plan'];
                                $isActiveUrgencia = in_array($_GET["ruta"], $rutasActivasUrgencia) ? 'active' : '';
                                ?>
                                <li class="treeview <?= $isActiveUrgencia; ?>">
                                    <a href="#"><i class="fa fa-circle-o"></i> Urgencias
                                        <span class="pull-right-container">
                                            <i class="fa fa-angle-left pull-right"></i>
                                        </span>
                                    </a>
                                    <ul class="treeview-menu">
                                        <li class="<?= $_GET['ruta'] == 'urgencias' ? 'active' : '' ?>">
                                            <a href="urgencias"><i class="fa fa-circle-o"></i> Urgencia APT</a>
                                        </li>
                                        <li class="<?= $_GET['ruta'] == 'urgencias-maestro' ? 'active' : '' ?>">
                                            <a href="urgencias-maestro"><i class="fa fa-circle-o"></i> Urg. Maestro</a>
                                        </li>
                                        <li class="<?= $_GET['ruta'] == 'urgencias-produccion' ? 'active' : '' ?>">
                                            <a href="urgencias-produccion"><i class="fa fa-circle-o"></i> Urg. Prod.</a>
                                        </li>
                                        <li class="<?= $_GET['ruta'] == 'urgencias-almacen' ? 'active' : '' ?>">
                                            <a href="urgencias-almacen"><i class="fa fa-circle-o"></i> Urg. A. Corte</a>
                                        </li>
                                        <li class="<?= $_GET['ruta'] == 'urgencias-corte' ? 'active' : '' ?>">
                                            <a href="urgencias-corte"><i class="fa fa-circle-o"></i> Urg. Corte</a>
                                        </li>
                                        <li class="<?= $_GET['ruta'] == 'urgencias-plan' ? 'active' : '' ?>">
                                            <a href="urgencias-plan"><i class="fa fa-circle-o"></i> Urg. Plan</a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </li>



                    </ul>
                </li>


            <?php endif ?>

            <!-- Tarjetas-->
            <?php
            if ($_SESSION["tarjetas"] == 1) {
            ?>

                <li class="treeview <?php if (
                                        $_GET["ruta"] == "tarjetas" ||
                                        $_GET["ruta"] == "ficha-tecnica" ||
                                        $_GET["ruta"] == "crear-tarjeta"
                                    ) echo 'active'; ?>">

                    <a href="#">

                        <i class="fa fa-id-card-o text-primary"></i>

                        <span>Tarjetas</span>

                        <span class="pull-right-container">

                            <i class="fa fa-angle-left pull-right"></i>

                        </span>

                    </a>

                    <ul class="treeview-menu">

                        <li class="<?php if ($_GET["ruta"] == "tarjetas") echo 'active'; ?>">

                            <a href="tarjetas">

                                <i class="fa fa-circle-o"></i>
                                <span>Administrar Tarjetas</span>

                            </a>

                        </li>
                        <li class="<?php if ($_GET["ruta"] == "ficha-tecnica") echo 'active'; ?>">

                            <a href="ficha-tecnica">

                                <i class="fa fa-circle-o"></i>
                                <span>Fichas tecnicas</span>

                            </a>

                        </li>
                        <li class="<?php if ($_GET["ruta"] == "crear-tarjeta") echo 'active'; ?>">

                            <a href="crear-tarjeta">

                                <i class="fa fa-circle-o"></i>
                                <span>Crear Tarjeta</span>

                            </a>

                        </li>

                    </ul>

                </li>


            <?php
            }
            ?>

            <!-- Operaciones -->
            <?php
            if ($_SESSION["operaciones"] == 1) {
            ?>

                <li class="<?php if (
                                $_GET["ruta"] == "detalleoperaciones" ||
                                $_GET["ruta"] == "creardetalleoperaciones" ||
                                $_GET["ruta"] == "editardetalleoperaciones"
                            ) echo 'active'; ?>">

                    <a href="detalleoperaciones">
                        <i class="fa fa-bolt text-yellow"></i>
                        <span>Operaciones Modelos</span>
                    </a>

                </li>

            <?php
            }
            ?>

            <!-- Clientes-->
            <?php
            if ($_SESSION["materiaprima"] == 1) {
            ?>
                <li class="treeview <?php if (
                                        $_GET["ruta"] == "materiaprima" ||
                                        $_GET["ruta"] == "notas-ingresos" ||
                                        $_GET["ruta"] == "crear-nota-ingreso" ||
                                        $_GET["ruta"] == "notas-salidas" ||
                                        $_GET["ruta"] == "crear-nota-salida" ||
                                        $_GET["ruta"] == "tabla-maestra" ||
                                        $_GET["ruta"] == "orden-compra" ||
                                        $_GET["ruta"] == "crear-orden-compra" ||
                                        $_GET["ruta"] == "editar-orden-compra" ||
                                        $_GET["ruta"] == "proveedor" ||
                                        $_GET["ruta"] == "orden-servicio" ||
                                        $_GET["ruta"] == "crear-orden-servicio" ||
                                        $_GET["ruta"] == "crear-nota-ingreso-os" ||
                                        $_GET["ruta"] == "notas-ingresos-os" ||
                                        $_GET["ruta"] == "kardex" ||
                                        $_GET["ruta"] == "mp-oc-pendiente" ||
                                        $_GET["ruta"] == "mp-os-pendiente" ||
                                        $_GET["ruta"] == "almacen-01" ||
                                        $_GET["ruta"] == "crear-cuadros-prod"
                                    ) echo 'active'; ?>">

                    <a href="#">

                        <i class="fa fa-scissors text-orange"></i> <span>Materia Prima</span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>

                    </a>

                    <ul class="treeview-menu">

                        <li class="<?php if ($_GET["ruta"] == "tabla-maestra") echo 'active'; ?>">

                            <a href="tabla-maestra">

                                <i class="fa fa-database text-blue"></i>
                                <span> Maestras Mp</span>

                            </a>

                        </li>

                        <li class="treeview <?php if (
                                                $_GET["ruta"] == "materiaprima" ||
                                                $_GET["ruta"] == "materiaprima-test" ||
                                                $_GET["ruta"] == "almacen-01"
                                            ) echo 'active'; ?>">

                            <a href="#"><i class="fa fa-scissors text-orange"></i> Materia Prima

                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>

                            </a>

                            <ul class="treeview-menu">

                                <?php 
                                // Determinar qué rutas mostrar según la configuración
                                $tipoPaginacionMP = (defined('TIPO_PAGINACION_MATERIAPRIMA')) ? TIPO_PAGINACION_MATERIAPRIMA : "cliente";
                                
                                if ($tipoPaginacionMP === "ambos") {
                                    // Mostrar ambas opciones para comparar
                                    ?>
                                    <li class="<?php if ($_GET["ruta"] == "materiaprima") echo 'active'; ?>">

                                        <a href="materiaprima">
                                            <i class="fa fa-circle-o text-blue"></i>
                                            <span>Materia Prima (Cliente)</span>
                                        </a>

                                    </li>

                                    <li class="<?php if ($_GET["ruta"] == "materiaprima-test") echo 'active'; ?>">

                                        <a href="materiaprima-test">
                                            <i class="fa fa-circle-o text-orange"></i>
                                            <span>Materia Prima (Servidor)</span>
                                        </a>

                                    </li>
                                    <?php
                                } else {
                                    // Mostrar solo una opción según la configuración
                                    ?>
                                    <li class="<?php if ($_GET["ruta"] == "materiaprima" || $_GET["ruta"] == "materiaprima-test") echo 'active'; ?>">

                                        <a href="<?php echo obtenerRutaMateriaPrima(); ?>">
                                            <i class="fa fa-circle-o"></i> Materia Prima
                                        </a>

                                    </li>
                                    <?php
                                }
                                ?>

                                <li class="<?php if ($_GET["ruta"] == "almacen-01") echo 'active'; ?>">

                                    <a href="almacen-01">
                                        <i class="fa fa-circle-o"></i> Almacén 01
                                    </a>

                                </li>

                            </ul>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "tabla-produccion") echo 'active'; ?>">

                            <a href="tabla-produccion">

                                <i class="fa fa-cogs text-yellow"></i> Produccion Cuadros
                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "orden-servicio") echo 'active'; ?>">

                            <a href="orden-servicio">

                                <i class="fa fa-paint-brush text-red"></i>
                                <span> Orden Servicio</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "notas-ingresos-os") echo 'active'; ?>">

                            <a href="notas-ingresos-os">

                                <i class="fa fa-file-o text-red"></i>
                                <span> Ingresos x OS</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "orden-compra") echo 'active'; ?>">

                            <a href="orden-compra">

                                <i class="fa fa-shopping-basket text-yellow"></i>
                                <span> Orden Compra</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "notas-ingresos") echo 'active'; ?>">

                            <a href="notas-ingresos">

                                <i class="fa fa-file-o text-yellow"></i>
                                <span> Ingresos x OC</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "mp-oc-pendiente") echo 'active'; ?>">

                            <a href="mp-oc-pendiente">

                                <i class="fa fa-file-o text-green"></i>
                                <span> Mp Pendiente - OC</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "mp-os-pendiente") echo 'active'; ?>">

                            <a href="mp-os-pendiente">

                                <i class="fa fa-file-o text-green"></i>
                                <span> Mp Pendiente - OS</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "notas-salidas") echo 'active'; ?>">

                            <a href="notas-salidas">

                                <i class="fa fa-file-o text-danger"></i>
                                <span> Notas Salidas</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "kardex") echo 'active'; ?>">

                            <a href="kardex">

                                <i class="fa fa-random text-purple"></i>
                                <span> Kardex</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "proveedor") echo 'active'; ?>">
                            <a href="proveedor">

                                <i class="fa fa-truck"></i>
                                <span>Proveedor</span>

                            </a>

                        </li>
                    </ul>

                </li>

            <?php
            }
            ?>


            <!--  Facturacion-->
            <?php
            if ($_SESSION["facturacion"] == 1) {
            ?>
                <li class="treeview <?php if (
                                        $_GET["ruta"] == "pedidoscv" ||
                                        $_GET["ruta"] == "crear-pedidocv" ||
                                        $_GET["ruta"] == "escaneo-barcode-pedidocv" ||
                                        $_GET["ruta"] == "clientes"  ||
                                        $_GET["ruta"] == "grupos-empresariales"  ||
                                        $_GET["ruta"] == "guias-remision"  ||
                                        $_GET["ruta"] == "crear-pedidoscv" ||
                                        $_GET["ruta"] == "pedidos-generados"  ||
                                        $_GET["ruta"] == "pedidos-aprobados"  ||
                                        $_GET["ruta"] == "pedidos-apt" ||
                                        $_GET["ruta"] == "pedidos-confirmados" ||
                                        $_GET["ruta"] == "pedidos-facturados"  ||
                                        $_GET["ruta"] == "facturas" ||
                                        $_GET["ruta"] == "boletas" ||
                                        $_GET["ruta"] == "proformas" ||
                                        $_GET["ruta"] == "ver-nota-credito" ||
                                        $_GET["ruta"] == "procesar-ce" ||
                                        $_GET["ruta"] == "reportes-ventas" ||
                                        $_GET["ruta"] == "notas-credito" ||
                                        $_GET["ruta"] == "errores" ||
                                        $_GET["ruta"] == "cuadre-caja"
                                    ) echo 'active'; ?>">

                    <a href="#">

                        <i class="fa fa-cart-plus text-green"></i>

                        <span>Facturación</span>

                        <span class="pull-right-container">

                            <i class="fa fa-angle-left pull-right"></i>

                        </span>

                    </a>

                    <ul class="treeview-menu">
                        <li class="<?php if ($_GET["ruta"] == "clientes") echo 'active'; ?>">

                            <a href="clientes">

                                <i class="fa fa-users"></i>
                                <span>Clientes</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "grupos-empresariales") echo 'active'; ?>">

                            <a href="grupos-empresariales">

                                <i class="fa fa-sitemap"></i>
                                <span>Grupos empresariales</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "pedidoscv") echo 'active'; ?>">

                            <a href="pedidoscv">

                                <i class="fa fa-paper-plane"></i>
                                <span>Pedidos</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "escaneo-barcode-pedidocv") echo 'active'; ?>">

                            <a href="escaneo-barcode-pedidocv">

                                <i class="fa fa-barcode"></i>
                                <span>Escaneo pedido CV</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "reportes-ventas") echo 'active'; ?>">

                            <a href="reportes-ventas">

                                <i class="fa fa-file-text"></i>
                                <span>Reportes Ventas</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "procesar-ce") echo 'active'; ?>">

                            <a href="procesar-ce">

                                <i class="fa fa-plane"></i>
                                <span>Procesar CE</span>

                            </a>

                        </li>

                        <li class="treeview <?php if (
                                                $_GET["ruta"] == "guias-remision" ||
                                                $_GET["ruta"] == "crear-pedidoscv" ||
                                                $_GET["ruta"] == "pedidos-generados"  ||
                                                $_GET["ruta"] == "pedidos-aprobados"  ||
                                                $_GET["ruta"] == "pedidos-apt" ||
                                                $_GET["ruta"] == "pedidos-confirmados" ||
                                                $_GET["ruta"] == "pedidos-facturados"  ||
                                                $_GET["ruta"] == "facturas" ||
                                                $_GET["ruta"] == "boletas" ||
                                                $_GET["ruta"] == "proformas" ||
                                                $_GET["ruta"] == "ver-nota-credito" ||
                                                $_GET["ruta"] == "notas-credito" ||
                                                $_GET["ruta"] == "errores" ||
                                                $_GET["ruta"] == "cuadre-caja"
                                            ) echo 'active'; ?>">

                            <a href="#"><i class="fa fa-clipboard"></i> Documentos

                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>

                            </a>

                            <ul class="treeview-menu">

                                <li class="<?php if ($_GET["ruta"] == "guias-remision") echo 'active'; ?>">

                                    <a href="guias-remision">

                                        <i class="fa fa-circle-o text-blue"></i>
                                        <span>Guias Remisión</span>

                                    </a>

                                </li>

                                <li class="<?php if ($_GET["ruta"] == "facturas") echo 'active'; ?>">

                                    <a href="facturas">

                                        <i class="fa fa-circle-o text-green"></i>
                                        <span>Facturas</span>

                                    </a>

                                </li>

                                <li class="<?php if ($_GET["ruta"] == "boletas") echo 'active'; ?>">

                                    <a href="boletas">

                                        <i class="fa fa-circle-o text-yellow"></i>
                                        <span>Boletas</span>

                                    </a>

                                </li>

                                <li class="<?php if ($_GET["ruta"] == "proformas") echo 'active'; ?>">

                                    <a href="proformas">

                                        <i class="fa fa-circle-o text-orange"></i>
                                        <span>Proformas</span>

                                    </a>

                                </li>

                                <li class="<?php if ($_GET["ruta"] == "ver-nota-credito") echo 'active'; ?>">

                                    <a href="ver-nota-credito">

                                        <i class="fa fa-circle-o text-green"></i>
                                        <span>NC/ND</span>

                                    </a>

                                </li>

                                <li class="<?php if ($_GET["ruta"] == "errores") echo 'active'; ?>">

                                    <a href="errores">

                                        <i class="fa fa-exclamation-circle text-red"></i>
                                        <span>Errores</span>

                                    </a>

                                </li>

                                <li class="<?php if ($_GET["ruta"] == "cuadre-caja") echo 'active'; ?>">

                                    <a href="cuadre-caja">

                                        <i class="fa fa-calculator text-primary"></i>
                                        <span>Cudrar caja</span>

                                    </a>

                                </li>

                            </ul>

                        </li>


                    </ul>

                </li>

            <?php
            }
            if ($_SESSION["cuenta"] == 1) {
            ?>
                <li class="treeview <?php if (
                                        $_GET["ruta"] == "cuentas" ||
                                        $_GET["ruta"] == "cuentas-test" ||
                                        $_GET["ruta"] == "cuentas-pendientes" ||
                                        $_GET["ruta"] == "cuentas-canceladas" ||
                                        $_GET["ruta"] == "abonos" ||
                                        $_GET["ruta"] == "cancelar-abonos" ||
                                        $_GET["ruta"] == "consultar-cuentas" ||
                                        $_GET["ruta"] == "ver-envio-letras" ||
                                        $_GET["ruta"] == "envio-letras" ||
                                        $_GET["ruta"] == "reportes-generales" ||
                                        $_GET["ruta"] == "notificaciones" ||
                                        $_GET["ruta"] == "letras-plazo-protesto" ||
                                        $_GET["ruta"] == "credipagos"
                                    ) echo 'active'; ?>">

                    <a href="#">

                        <i class="fa fa-money text-green"></i>

                        <span>Cuentas corrientes</span>

                        <span class="pull-right-container">

                            <i class="fa fa-angle-left pull-right"></i>

                        </span>

                    </a>

                    <ul class="treeview-menu">
                        <li class="treeview <?php if (
                                                $_GET["ruta"] == "cuentas" ||
                                                $_GET["ruta"] == "cuentas-test" ||
                                                $_GET["ruta"] == "cuentas-pendientes" ||
                                                $_GET["ruta"] == "cuentas-canceladas" ||
                                                $_GET["ruta"] == "abonos" ||
                                                $_GET["ruta"] == "cancelar-abonos" ||
                                                $_GET["ruta"] == "consultar-cuentas" ||
                                                $_GET["ruta"] == "ver-envio-letras" ||
                                                $_GET["ruta"] == "reportes-generales" ||
                                                $_GET["ruta"] == "notificaciones" ||
                                                $_GET["ruta"] == "letras-plazo-protesto" ||
                                                $_GET["ruta"] == "credipagos"
                                            ) echo 'active'; ?>">

                            <a href="#"><i class="fa fa-clipboard"></i> Cuentas

                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>

                            </a>

                            <ul class="treeview-menu">

                                <?php 
                                // Determinar qué rutas mostrar según la configuración
                                $tipoPaginacion = (defined('TIPO_PAGINACION_CUENTAS')) ? TIPO_PAGINACION_CUENTAS : "cliente";
                                
                                if ($tipoPaginacion === "ambos") {
                                    // Mostrar ambas opciones para comparar
                                    ?>
                                    <li class="<?php if ($_GET["ruta"] == "cuentas") echo 'active'; ?>">

                                        <a href="cuentas">

                                            <i class="fa fa-circle-o text-blue"></i>
                                            <span>Generales (Cliente)</span>

                                        </a>

                                    </li>

                                    <li class="<?php if ($_GET["ruta"] == "cuentas-test") echo 'active'; ?>">

                                        <a href="cuentas-test">

                                            <i class="fa fa-circle-o text-orange"></i>
                                            <span>Generales (Servidor)</span>

                                        </a>

                                    </li>
                                    <?php
                                } else {
                                    // Mostrar solo una opción según la configuración
                                    $rutaCuentas = ($tipoPaginacion === "servidor") ? "cuentas-test" : "cuentas";
                                    $rutaActiva = ($_GET["ruta"] == "cuentas" || $_GET["ruta"] == "cuentas-test");
                                    ?>
                                    <li class="<?php if ($rutaActiva) echo 'active'; ?>">

                                        <a href="<?php echo $rutaCuentas; ?>">

                                            <i class="fa fa-circle-o text-blue"></i>
                                            <span>Generales</span>

                                        </a>

                                    </li>
                                    <?php
                                }
                                ?>


                            </ul>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "notificaciones") echo 'active'; ?>">

                            <a href="notificaciones">

                                <i class="fa fa-bell"></i>
                                <span>Notificaciones</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "letras-plazo-protesto") echo 'active'; ?>">

                            <a href="letras-plazo-protesto">

                                <i class="fa fa-hourglass-half"></i>
                                <span>Letras a informar hoy</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "abonos") echo 'active'; ?>">

                            <a href="abonos">

                                <i class="fa fa-circle-o"></i>
                                <span>Abonos</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "cancelar-abonos") echo 'active'; ?>">
                            <a href="cancelar-abonos">

                                <i class="fa fa-circle-o"></i>
                                <span>Cancelar abonos</span>

                            </a>
                        </li>

                        <li class="<?php if ($_GET["ruta"] == "consultar-cuentas") echo 'active'; ?>">
                            <a href="consultar-cuentas">

                                <i class="fa fa-circle-o"></i>
                                <span>Consultar cuentas</span>

                            </a>
                        </li>

                        <li class="<?php if ($_GET["ruta"] == "ver-envio-letras") echo 'active'; ?>">
                            <a href="ver-envio-letras">

                                <i class="fa fa-circle-o"></i>
                                <span>Envio letras</span>

                            </a>
                        </li>

                        <li class="<?php if ($_GET["ruta"] == "credipagos") echo 'active'; ?>">
                            <a href="credipagos">

                                <i class="fa fa-circle-o"></i>
                                <span>Credipagos</span>

                            </a>
                        </li>

                        <li class="<?php if ($_GET["ruta"] == "reportes-generales") echo 'active'; ?>">
                            <a href="reportes-generales">

                                <i class="fa fa-circle-o"></i>
                                <span>Reportes Generales</span>

                            </a>
                        </li>
                    </ul>
                </li>

            <?php
            }
            ?>

            <!-- Vasco Online (API) -->
            <?php
            $puedeVerSyncVasco = function_exists("usuarioPuedeVerModulo")
                && usuarioPuedeVerModulo("vasco_online", "sincronizacion");
            $puedeVerRendicionVasco = function_exists("usuarioPuedeVerModulo")
                && usuarioPuedeVerModulo("vasco_online", "rendicion_cobranzas");
            $puedeVerGestionClientesVasco = function_exists("usuarioPuedeVerModulo")
                && usuarioPuedeVerModulo("vasco_online", "gestion_clientes");
            $puedeVerSolicitudesAtencionVasco = function_exists("usuarioPuedeVerModulo")
                && usuarioPuedeVerModulo("vasco_online", "solicitudes_atencion");

            $mostrarVascoOnline = function_exists("usuarioPuedeAlgunaOpcionSector")
                && usuarioPuedeAlgunaOpcionSector("vasco_online");

            if ($mostrarVascoOnline) {
                $rutasActivasVascoOnline = array();
                if ($puedeVerSyncVasco) {
                    $rutasActivasVascoOnline[] = "sync-vasco";
                }
                if ($puedeVerRendicionVasco) {
                    $rutasActivasVascoOnline[] = "rendicion-vasco-caja";
                }
                if ($puedeVerGestionClientesVasco) {
                    $rutasActivasVascoOnline[] = "gestion-vasco-clientes";
                }
                if ($puedeVerSolicitudesAtencionVasco) {
                    $rutasActivasVascoOnline[] = "solicitudes-atencion-vasco";
                }
                $isActiveVascoOnline = in_array($_GET["ruta"], $rutasActivasVascoOnline, true) ? "active" : "";
            ?>
                <li class="treeview <?= $isActiveVascoOnline; ?>">

                    <a href="#">

                        <i class="fa fa-cloud text-light-blue"></i>

                        <span>Vasco Online</span>

                        <span class="pull-right-container">

                            <i class="fa fa-angle-left pull-right"></i>

                        </span>

                    </a>

                    <ul class="treeview-menu">

                        <?php if ($puedeVerSyncVasco) { ?>
                        <li class="<?= $_GET["ruta"] == "sync-vasco" ? "active" : ""; ?>">
                            <a href="sync-vasco">
                                <i class="fa fa-refresh"></i>
                                <span>Sincronización</span>
                            </a>
                        </li>
                        <?php } ?>

                        <?php if ($puedeVerRendicionVasco) { ?>
                        <li class="<?= $_GET["ruta"] == "rendicion-vasco-caja" ? "active" : ""; ?>">
                            <a href="rendicion-vasco-caja">
                                <i class="fa fa-handshake-o"></i>
                                <span>Rendición cobranzas</span>
                            </a>
                        </li>
                        <?php } ?>

                        <?php if ($puedeVerGestionClientesVasco) { ?>
                        <li class="<?= $_GET["ruta"] == "gestion-vasco-clientes" ? "active" : ""; ?>">
                            <a href="gestion-vasco-clientes">
                                <i class="fa fa-whatsapp"></i>
                                <span>Gestión clientes</span>
                            </a>
                        </li>
                        <?php } ?>

                        <?php if ($puedeVerSolicitudesAtencionVasco) { ?>
                        <li class="<?= $_GET["ruta"] == "solicitudes-atencion-vasco" ? "active" : ""; ?>">
                            <a href="solicitudes-atencion-vasco">
                                <i class="fa fa-bell"></i>
                                <span>Solicitudes atención</span>
                            </a>
                        </li>
                        <?php } ?>

                    </ul>

                </li>

            <?php
            }
            ?>

                <!--  Costos-->
            <?php
            if ($_SESSION["caja"] == 1) {
            ?>
                <li class="treeview <?php if (
                                        $_GET["ruta"] == "centro-costos" ||
                                        $_GET["ruta"] == "gastos-caja" ||
                                        $_GET["ruta"] == "ingresos-caja" ||
                                        $_GET["ruta"] == "centro-costos-rsm" ||
                                        $_GET["ruta"] == "solicitud-caja" ||
                                        $_GET["ruta"] == "kardex-carga"
                                    ) echo 'active'; ?>">

                    <a href="#">

                        <i class="fa fa-rocket text-yellow"></i>

                        <span>Costos</span>

                        <span class="pull-right-container">

                            <i class="fa fa-angle-left pull-right"></i>

                        </span>

                    </a>

                    <ul class="treeview-menu">

                        <li class="<?php if ($_GET["ruta"] == "centro-costos") echo 'active'; ?>">

                            <a href="centro-costos">

                                <i class="fa fa-cc text-yellow"></i>
                                <span>Centro de Costos</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "kardex-carga") echo 'active'; ?>">

                            <a href="kardex-carga">

                                <i class="fa fa-cc text-yellow"></i>
                                <span>Kardex</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "gastos-caja") echo 'active'; ?>">

                            <a href="gastos-caja">

                                <i class="fa fa-diamond text-red"></i>
                                <span>Gastos Caja ( - )</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "solicitud-caja") echo 'active'; ?>">

                            <a href="solicitud-caja">

                                <i class="fa fa-diamond text-red"></i>
                                <span>Solicitud Gasto ( - )</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "ingresos-caja") echo 'active'; ?>">

                            <a href="ingresos-caja">

                                <i class="fa fa-diamond text-blue"></i>
                                <span>Ingresos Caja ( + )</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "centro-costos-rsm") echo 'active'; ?>">

                            <a href="centro-costos-rsm">

                                <i class="fa fa-cc text-yellow"></i>
                                <span>Resumen CC</span>

                            </a>

                        </li>

                    </ul>

                </li>

                <!--  Ticket-->
            <?php
            }
            if ($_SESSION["costos"] == 1) {
            ?>
                <li class="treeview <?php if (
                                        $_GET["ruta"] == "centro-costos" ||
                                        $_GET["ruta"] == "diario" ||
                                        $_GET["ruta"] == "diario-alerta" ||
                                        $_GET["ruta"] == "compras-reg" ||
                                        $_GET["ruta"] == "costos-modelo" ||
                                        $_GET["ruta"] == "costos-versus"
                                    ) echo 'active'; ?>">

                    <a href="#">

                        <i class="fa fa-cc text-red"></i>

                        <span>Centro de Costos</span>

                        <span class="pull-right-container">

                            <i class="fa fa-angle-left pull-right"></i>

                        </span>

                    </a>

                    <ul class="treeview-menu">

                        <li class="<?php if ($_GET["ruta"] == "centro-costos") echo 'active'; ?>">

                            <a href="centro-costos">

                                <i class="fa fa-cc text-yellow"></i>
                                <span>Centro de Costos</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "diario") echo 'active'; ?>">

                            <a href="diario">

                                <i class="fa fa-book text-blue"></i>
                                <span>Diario</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "diario-alerta") echo 'active'; ?>">

                            <a href="diario-alerta">

                                <i class="fa fa-book text-red"></i>
                                <span>Diario-Alerta</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "compras-reg") echo 'active'; ?>">

                            <a href="compras-reg">

                                <i class="fa fa-cart-arrow-down text-red"></i>
                                <span>Compras</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "validar-documento") echo 'active'; ?>">

                            <a href="validar-documento">

                                <i class="fa fa-search text-white"></i>
                                <span>Validar</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "costos-modelo") echo 'active'; ?>">

                            <a href="costos-modelo">

                                <i class="fa fa-star text-yellow"></i>
                                <span>Costos Por Modelo</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "costos-versus") echo 'active'; ?>">

                            <a href="costos-versus">

                                <i class="fa fa-star text-white"></i>
                                <span>Comparación de Costos</span>

                            </a>

                        </li>

                    </ul>

                </li>

                <!--  Ticket-->
            <?php
            }
            if ($_SESSION["ticket"] == 1) {
            ?>
                <li class="treeview <?php if (
                                        $_GET["ruta"] == "contactos" ||
                                        $_GET["ruta"] == "mailbox"
                                    ) echo 'active'; ?>">

                    <a href="#">

                        <i class="fa fa-inbox text-blue"></i>

                        <span>Ticket</span>

                        <span class="pull-right-container">

                            <i class="fa fa-angle-left pull-right"></i>

                        </span>

                    </a>

                    <ul class="treeview-menu">

                        <li class="<?php if ($_GET["ruta"] == "contactos") echo 'active'; ?>">

                            <a href="contactos">

                                <i class="fa fa-users"></i>
                                <span>Contactos</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "mailbox") echo 'active'; ?>">

                            <a href="mailbox">

                                <i class="fa fa-envelope-o"></i>
                                <span>Mailbox</span>

                            </a>

                        </li>

                    </ul>

                </li>

            <?php
            }
            if ($_SESSION["mantenimiento"] == 1) {
            ?>

                <li class="treeview <?php if (
                                        $_GET["ruta"] == "mantenimiento" ||
                                        $_GET["ruta"] == "equipos" ||
                                        $_GET["ruta"] == "calendario"
                                    ) echo 'active'; ?>">

                    <a href="#">

                        <i class="fa fa-industry text-white"></i>

                        <span>Mantenimiento</span>

                        <span class="pull-right-container">

                            <i class="fa fa-angle-left pull-right"></i>

                        </span>

                    </a>

                    <ul class="treeview-menu">

                        <li class="<?php if ($_GET["ruta"] == "equipos") echo 'active'; ?>">

                            <a href="equipos">

                                <i class="fa fa-wrench"></i>
                                <span>Equipos</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "calendario") echo 'active'; ?>">

                            <a href="calendario">

                                <i class="fa fa-calendar"></i>
                                <span>Calendario</span>

                            </a>

                        </li>

                        <li class="<?php if ($_GET["ruta"] == "mantenimiento") echo 'active'; ?>">

                            <a href="mantenimiento">

                                <i class="fa fa-wrench"></i>
                                <span>Mantenimiento</span>

                            </a>

                        </li>

                    </ul>

                </li>

            <?php
            }
            ?>


        </ul>

    </section>

</aside>

<script>
    $(".search-menu-box").on('input', function() {
        var filter = $(this).val();
        $(".sidebar-menu > li").each(function() {
            if ($(this).text().search(new RegExp(filter, "i")) < 0) {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
    });
</script>