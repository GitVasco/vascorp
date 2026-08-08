<header class="main-header">

    <!--=====================================
     LOGOTIPO
     ======================================-->
    <a href="inicio" class="logo">

        <!-- logo mini -->
        <span class="logo-mini">

            <img src="vistas/img/plantilla/vascorp.png" class="img-responsive" style="padding:10px">

        </span>

        <!-- logo normal -->

        <span class="logo-lg">

            <img src="vistas/img/plantilla/vasco.png" class="img-responsive" style="padding:10px 0px">

        </span>

    </a>

    <!--=====================================
     BARRA DE NAVEGACIÓN
     ======================================-->
    <nav class="navbar navbar-static-top" role="navigation">

        <!-- Botón de navegación -->

        <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">

            <span class="sr-only">Toggle navigation</span>

        </a>

        <!-- perfil de usuario -->

        <div class="navbar-custom-menu">

            <ul class="nav navbar-nav">

                <li class="dropdown task-menu">
                        <a class="button prbBtn" fecha="<?php echo date('Y-m-d') ?>" id="btnActTC" onclick="actualizarTC()">
                            <i class="fa fa-usd"></i>

                        </a>


                </li>

                <?php
                if (class_exists("ControladorHelpdesk") && ControladorHelpdesk::ctrPuede("gestionar")) {
                    $hdNav = ControladorHelpdesk::ctrContarAbiertosNavbar();
                    $hdAbiertos = isset($hdNav["abiertos"]) ? (int) $hdNav["abiertos"] : 0;
                    $hdNavTitulo = !empty($hdNav["control_total"])
                        ? "Helpdesk · tickets abiertos (todos)"
                        : "Helpdesk · tickets abiertos (míos y sin asignar)";
                    $hdBadgeCls = $hdAbiertos > 0 ? "label-warning" : "label-default";
                    $hdBadgeTxt = $hdAbiertos > 99 ? "99+" : (string) $hdAbiertos;
                ?>
                <li class="dropdown notifications-menu">
                    <a href="helpdesk" title="<?php echo htmlspecialchars($hdNavTitulo, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fa fa-ticket"></i>
                        <span class="label <?php echo $hdBadgeCls; ?>"><?php echo htmlspecialchars($hdBadgeTxt, ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                </li>
                <?php
                }
                ?>

                <li class="dropdown user user-menu">

                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">

                        <?php

                        if ($_SESSION["foto"] != "") {

                            echo '<img src="' . $_SESSION["foto"] . '" class="user-image">';
                        } else {


                            echo '<img src="vistas/img/usuarios/default/anonymous.png" class="user-image">';
                        }


                        ?>

                        <span class="hidden-xs"><?php echo $_SESSION["nombre"]; ?></span>

                    </a>

                    <!-- Dropdown-toggle -->

					<ul class="dropdown-menu">
						<!-- User image -->
						<li class="user-header">
						<?php

							if($_SESSION["foto"] != ""){

								echo '<img src="'.$_SESSION["foto"].'" class="img-circle" alt="User Image">';

							}else{


								echo '<img src="vistas/img/usuarios/default/anonymous.png" class="img-circle" alt="User Image">';

							}


						?>
							
							<p>
							<?php  echo $_SESSION["nombre"]; ?>
							</p>
                            <small style="color:rgba(255, 255, 255, 0.8)">( <?php  echo $_SESSION["perfil"]; ?> )</small>
						</li>
						<!-- Menu Footer-->
						<li class="user-footer " >
							
							<div class="pull-right">
							<a href="salir" class="btn btn-default btn-flat">Salir</a>
							</div>
						</li>
					</ul>
                </li>

            </ul>

        </div>

    </nav>

</header>