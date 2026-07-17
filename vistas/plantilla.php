<?php

if (!isset($_SESSION)) {
    session_start();
}

/* Compatibilidad temporal: pedidoscv-vendedores → Centro de Decisiones */
if (isset($_GET["ruta"]) && $_GET["ruta"] === "pedidoscv-vendedores") {
    header("Location: index.php?ruta=dashboard-decisiones");
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="google" content="notranslate">
    <?php

    $__tituloVentana = "Vasco System";
    if (isset($_GET["ruta"])) {
        if ($_GET["ruta"] === "escaneo-barcode-pedidocv") {
            $__tituloVentana = "Crear pedido · Código de barras | Vasco System";
        } elseif ($_GET["ruta"] === "crear-pedidocv") {
            $__tituloVentana = "Crear pedido | Vasco System";
        } elseif ($_GET["ruta"] === "dashboard-cobranzas") {
            $__tituloVentana = "Dashboard de Cobranzas | Vasco System";
        } elseif ($_GET["ruta"] === "dashboard-decisiones") {
            $__tituloVentana = "Centro de Decisiones | Vasco System";
        } elseif ($_GET["ruta"] === "historial-credito") {
            $__tituloVentana = "Historial de crédito | Vasco System";
        } elseif ($_GET["ruta"] === "metas-vendedor") {
            $__tituloVentana = "Metas vendedor | Vasco System";
        } elseif ($_GET["ruta"] === "metas-retos") {
            $__tituloVentana = "Metas / retos | Vasco System";
        } elseif ($_GET["ruta"] === "costos-mensuales-modelo") {
            $__tituloVentana = "Costos mensuales por modelo | Vasco System";
        } elseif ($_GET["ruta"] === "ficha-gerencial-modelos") {
            $__tituloVentana = "Ficha gerencial de modelos | Vasco System";
        } elseif ($_GET["ruta"] === "resumen-gerencial-modelos") {
            $__tituloVentana = "Resumen gerencial de modelos | Vasco System";
        } elseif ($_GET["ruta"] === "zonas-comerciales") {
            $__tituloVentana = "Zonas comerciales | Vasco System";
        } elseif ($_GET["ruta"] === "grupos-marcas") {
            $__tituloVentana = "Grupos de marcas | Vasco System";
        } elseif ($_GET["ruta"] === "asignacion-grupos-marcas") {
            $__tituloVentana = "Asignación de grupos de marcas | Vasco System";
        } elseif ($_GET["ruta"] === "mapas-zonas") {
            $__tituloVentana = "Mapas de zonas | Vasco System";
        } elseif ($_GET["ruta"] === "linea-credito") {
            $__tituloVentana = "Línea de crédito | Vasco System";
        } elseif ($_GET["ruta"] === "descuentos-compuestos") {
            $__tituloVentana = "Descuentos Compuestos ESSO | Vasco System";
        } elseif ($_GET["ruta"] === "inteligencia-comercial") {
            $__tituloVentana = "Inteligencia Comercial | Vasco System";
        }
    }
    ?>

    <title><?php echo htmlspecialchars($__tituloVentana, ENT_QUOTES, "UTF-8"); ?></title>

    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

    <link rel="icon" href="vistas/img/plantilla/vascorp.png">

    <!--=====================================
  PLUGINS DE CSS
  ======================================-->

    <!-- Bootstrap 3.3.7 -->
    <link rel="stylesheet" href="vistas/bower_components/bootstrap/dist/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="vistas/bower_components/font-awesome/css/font-awesome.min.css">

    <!-- Ionicons -->
    <link rel="stylesheet" href="vistas/bower_components/Ionicons/css/ionicons.min.css">

    <!-- Theme style -->
    <link rel="stylesheet" href="vistas/dist/css/AdminLTE.css?v=<?php echo (rand()); ?>">

    <!-- AdminLTE Skins -->
    <link rel="stylesheet" href="vistas/dist/css/skins/_all-skins.min.css">

    <!-- Google Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">

    <!-- DataTables -->
    <link rel="stylesheet" href="vistas/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css">
    <link rel="stylesheet" href="vistas/bower_components/datatables.net-bs/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" href="vistas/bower_components/datatables.net-bs/css/fixedHeader.dataTables.min.css">

    <?php if (isset($_GET["ruta"]) && in_array($_GET["ruta"], ["pedidoscv", "pedidos-generados", "pedidos-aprobados", "pedidos-apt", "pedidos-confirmados", "pedidos-facturados"], true)) : ?>
    <link rel="stylesheet" href="vistas/css/pedidos-tablas-acciones.css?v=<?php echo rand(); ?>">
    <?php endif; ?>

    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "dashboard-decisiones") : ?>
    <link rel="stylesheet" href="vistas/css/dashboard-decisiones.css?v=<?php echo rand(); ?>">
    <?php endif; ?>

    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "historial-credito") : ?>
    <link rel="stylesheet" href="vistas/css/dashboard-decisiones.css?v=<?php echo rand(); ?>">
    <link rel="stylesheet" href="vistas/css/historial-credito.css?v=<?php echo rand(); ?>">
    <?php endif; ?>

    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "linea-credito") : ?>
    <link rel="stylesheet" href="vistas/css/linea-credito.css?v=<?php echo rand(); ?>">
    <?php endif; ?>

    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "ficha-gerencial-modelos") : ?>
    <link rel="stylesheet" href="vistas/css/ficha-gerencial-modelos.css?v=35">
    <?php endif; ?>

    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "resumen-gerencial-modelos") : ?>
    <link rel="stylesheet" href="vistas/css/resumen-gerencial-modelos.css?v=4">
    <?php endif; ?>

    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "sync-vasco") : ?>
    <link rel="stylesheet" href="vistas/css/vasco-online-sync.css?v=<?php echo rand(); ?>">
    <?php endif; ?>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "rendicion-vasco-caja") : ?>
    <link rel="stylesheet" href="vistas/css/vasco-cobranzas-caja.css?v=<?php echo rand(); ?>">
    <?php endif; ?>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "gestion-vasco-clientes") : ?>
    <link rel="stylesheet" href="vistas/css/vasco-gestion-cliente.css?v=<?php echo rand(); ?>">
    <?php endif; ?>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "solicitudes-atencion-vasco") : ?>
    <link rel="stylesheet" href="vistas/css/vasco-solicitud-atencion.css?v=<?php echo rand(); ?>">
    <?php endif; ?>

    <!-- iCheck for checkboxes and radio inputs -->
    <link rel="stylesheet" href="vistas/plugins/iCheck/all.css">
    <!-- SELECT 2 -->
    <!-- <link rel="stylesheet" href="vistas/bower_components/select2/dist/css/select2.min.css"> -->

    <!-- SELECT bootstrap -->
    <link rel="stylesheet" href="vistas/bower_components/bootstrap-select/dist/css/bootstrap-select.min.css">

    <!-- Daterange picker -->
    <link rel="stylesheet" href="vistas/bower_components/bootstrap-daterangepicker/daterangepicker.css">

    <!-- Morris chart -->
    <link rel="stylesheet" href="vistas/bower_components/morris.js/morris.css">

    <!-- Toastr -->
    <link rel="stylesheet" href="vistas/bower_components/toastr/toastr.min.css">

    <!-- fullCalendar -->
    <link rel="stylesheet" href="vistas/bower_components/fullcalendar/dist/fullcalendar.min.css">
    <link rel="stylesheet" href="vistas/bower_components/fullcalendar/dist/fullcalendar.print.min.css" media="print">

    <!--=====================================
  PLUGINS DE JAVASCRIPT
  ======================================-->

    <!-- jQuery 3 -->
    <script src="vistas/bower_components/jquery/dist/jquery.min.js"></script>

    <!-- Bootstrap 3.3.7 -->
    <script src="vistas/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>

    <!-- FastClick -->
    <script src="vistas/bower_components/fastclick/lib/fastclick.js"></script>

    <!-- AdminLTE App -->
    <script src="vistas/dist/js/adminlte.min.js"></script>

    <!-- DataTables -->
    <script src="vistas/bower_components/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="vistas/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
    <script src="vistas/bower_components/datatables.net-bs/js/dataTables.responsive.min.js"></script>
    <script src="vistas/bower_components/datatables.net-bs/js/responsive.bootstrap.min.js"></script>
    <script src="vistas/bower_components/datatables.net-bs/js/dataTables.fixedHeader.min.js"></script>

    <!-- SweetAlert 2 -->
    <!-- Incluye SweetAlert2 -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> -->

    <script src="vistas/plugins/sweetalert2/sweetalert2.all.js"></script>

    <!-- Select2 -->
    <!-- <script src="vistas/bower_components/select2/dist/js/select2.full.min.js"></script> -->

    <!-- By default SweetAlert2 doesn't support IE. To enable IE 11 support, include Promise polyfill:-->
    <!-- <script src="vistas/plugins/sweetalert2/core.js"></script> -->

    <!-- iCheck 1.0.1 -->
    <script src="vistas/plugins/iCheck/icheck.min.js"></script>

    <!-- InputMask -->
    <script src="vistas/plugins/input-mask/jquery.inputmask.js"></script>
    <script src="vistas/plugins/input-mask/jquery.inputmask.date.extensions.js"></script>
    <script src="vistas/plugins/input-mask/jquery.inputmask.extensions.js"></script>
    <script src="vistas/plugins/input-mask/jquery.mask.min.js"></script>

    <!-- bootstrap-select -->

    <script src="vistas/bower_components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>

    <!-- jQuery Number -->
    <script src="vistas/plugins/jqueryNumber/jquerynumber.min.js"></script>

    <!-- daterangepicker http://www.daterangepicker.com/-->
    <script src="vistas/bower_components/moment/min/moment.min.js"></script>
    <script src="vistas/bower_components/bootstrap-daterangepicker/daterangepicker.js"></script>

    <!-- Morris.js charts http://morrisjs.github.io/morris.js/-->
    <script src="vistas/bower_components/raphael/raphael.min.js"></script>
    <script src="vistas/bower_components/morris.js/morris.min.js"></script>

    <!-- ChartJS http://www.chartjs.org/-->
    <script src="vistas/bower_components/Chart.js/Chart.js"></script>

    <!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script> -->


    <!-- Libreria de suma datatable-->
    <script src="vistas/bower_components/sumas/sum().js"></script>

    <!-- Libreria de hora local -->
    <script src="vistas/bower_components/moment/locale/es.js"></script>

    <script src="vistas/bower_components/toastr/toastr.min.js"></script>

    <!-- fullCalendar -->
    <script src="vistas/bower_components/moment/moment.js"></script>
    <script src="vistas/bower_components/fullcalendar/dist/fullcalendar.min.js"></script>

    <!-- Material Preloader -->
    <!-- https://www.jqueryscript.net/loading/Google-Inbox-Style-Linear-Preloader-Plugin-with-jQuery-CSS3.html -->
    <script src="vistas/bower_components/material-preloader/material-preloader.js"></script>
    <!-- Notie Alert -->
    <!-- https://jaredreich.com/notie/ -->
    <!-- https://github.com/jaredreich/notie -->
    <script src="vistas/bower_components/notie/notie.min.js"></script>


    <style>
        .table thead,
        .table tfoot {
            background-color: #3c8dbc;
            color: white;
        }

        .azul {
            color: #0000FF;
        }

        .guinda {
            color: #8B0000;
        }

        .table>tbody>tr>th,
        .table>tfoot>tr>th,
        .table>thead>tr>td,
        .table>tbody>tr>td,
        .table>tfoot>tr>td {
            padding: 2px !important;
        }

        .table>thead>tr>th {
            padding: 5px !important;
        }

        @media (min-width: 1600px) {
            div.dataTables_wrapper div.dataTables_filter input {

                width: 500px;
                /* background-color:red; */
            }
        }

        @media (min-width: 1200px) and (max-width: 1600px) {
            div.dataTables_wrapper div.dataTables_filter input {

                width: 500px;
                /* background-color:red; */
            }
        }

        .btn-group .btn {
            margin-left: 20px !important;
        }

        .disable-div {
            pointer-events: none;
        }

        /* #areaChart {
            max-width: 1000px;
            margin: auto;
        }

        .chartjs-render-monitor {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            font-weight: bold;
            color: #333;
        } */
    </style>
</head>

<!--=====================================
CUERPO DOCUMENTO
======================================-->

<body class="hold-transition skin-black sidebar-collapse sidebar-mini login-page">

    <?php

    if (isset($_SESSION["iniciarSesion"]) && $_SESSION["iniciarSesion"] == "ok") {

        echo '<div class="wrapper">';

        /*=============================================
    CABEZOTE
    =============================================*/

        include "modulos/cabezote.php";

        /*=============================================
    MENU
    =============================================*/

        include "modulos/menu.php";

        /*=============================================
    CONTENIDO
    =============================================*/

        if (isset($_GET["ruta"])) {

            if (
                $_GET["ruta"] == "inicio" ||
                $_GET["ruta"] == "inicio-gerencia" ||
                $_GET["ruta"] == "refrescar"
            ) {

                include "modulos/" . $_GET["ruta"] . ".php";
            } else if ($_GET["ruta"] == "dashboard-cobranzas") {

                if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "dashboard_cobranzas")) {
                    denegarAccesoModulo();
                } else {
                    include "modulos/dashboard-cobranzas.php";
                }
            } else if ($_GET["ruta"] == "dashboard-decisiones") {

                if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "centro_decisiones")) {
                    denegarAccesoModulo();
                } else {
                    include "modulos/dashboard-decisiones.php";
                }
            } else if ($_GET["ruta"] == "historial-credito") {

                if (!function_exists("dcUsuarioPuedeVerHistorialCredito") || !dcUsuarioPuedeVerHistorialCredito()) {
                    denegarAccesoModulo();
                } else {
                    include "modulos/historial-credito.php";
                }
            } else if ($_GET["ruta"] == "metas-vendedor") {

                if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "metas_vendedor")) {
                    denegarAccesoModulo();
                } else {
                    include "modulos/metas-vendedor.php";
                }
            } else if ($_GET["ruta"] == "metas-retos") {

                if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "metas_vendedor")) {
                    denegarAccesoModulo();
                } else {
                    include "modulos/metas-retos.php";
                }
            } else if ($_GET["ruta"] == "costos-mensuales-modelo") {

                if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "costos_modelo")) {
                    denegarAccesoModulo();
                } else {
                    include "modulos/costos-mensuales-modelo.php";
                }
            } else if ($_GET["ruta"] == "ficha-gerencial-modelos") {

                if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "ficha_modelos")) {
                    denegarAccesoModulo();
                } else {
                    include "modulos/ficha-gerencial-modelos.php";
                }
            } else if ($_GET["ruta"] == "resumen-gerencial-modelos") {

                if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "ficha_modelos")) {
                    denegarAccesoModulo();
                } else {
                    include "modulos/resumen-gerencial-modelos.php";
                }
            } else if ($_GET["ruta"] == "linea-credito") {

                if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "linea_credito")) {
                    denegarAccesoModulo();
                } else {
                    include "modulos/linea-credito.php";
                }
            } else if ($_GET["ruta"] == "descuentos-compuestos") {

                include "modulos/descuentos-compuestos/descuentos-compuestos.php";
            } else if ($_GET["ruta"] == "inteligencia-comercial") {

                if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "inteligencia_comercial")) {
                    denegarAccesoModulo();
                } else {
                    include "modulos/inteligencia-comercial/inteligencia-comercial.php";
                }
            } else if ($_GET["ruta"] == "usuarios") {

                include "modulos/usuarios/" . $_GET["ruta"] . ".php";
            } else if ($_GET["ruta"] == "sync-vasco") {

                if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("vasco_online", "sincronizacion")) {
                    denegarAccesoModulo();
                } else {
                    include "modulos/vasco-online/sync-vasco.php";
                }
            } else if (
                $_GET["ruta"] == "backupDB" ||
                $_GET["ruta"] == "bkplista" ||
                $_GET["ruta"] == "movimientos" ||
                $_GET["ruta"] == "datos-dia" ||
                $_GET["ruta"] == "conexionjf"
            ) {

                include "modulos/backend/" . $_GET["ruta"] . ".php";
            } else if (
                $_GET["ruta"] == "m-produccion" ||
                $_GET["ruta"] == "m-ventas" ||
                $_GET["ruta"] == "mp-ingresos" ||
                $_GET["ruta"] == "mp-salidas"
            ) {

                include "modulos/movimientos/" . $_GET["ruta"] . ".php";
            } else if (
                $_GET["ruta"] == "articulos" ||
                $_GET["ruta"] == "marcas" ||
                $_GET["ruta"] == "colores" ||
                $_GET["ruta"] == "tipodocumentos" ||
                $_GET["ruta"] == "tipotrabajador" ||
                $_GET["ruta"] == "trabajador" ||
                $_GET["ruta"] == "trabajador2" ||
                $_GET["ruta"] == "operaciones" ||
                $_GET["ruta"] == "modelosjf" ||
                $_GET["ruta"] == "crear-articulo" ||
                $_GET["ruta"] == "sectores" ||
                $_GET["ruta"] == "paras" ||
                $_GET["ruta"] == "agencias" ||
                $_GET["ruta"] == "tipomovimientos" ||
                $_GET["ruta"] == "tipopagos" ||
                $_GET["ruta"] == "condicionesventa" ||
                $_GET["ruta"] == "unidadesmedida" ||
                $_GET["ruta"] == "bancos" ||
                $_GET["ruta"] == "vendedor" ||
                $_GET["ruta"] == "tabla-maestra"
            ) {

                include "modulos/maestros/" . $_GET["ruta"] . ".php";
            } else if (
                $_GET["ruta"] == "materiaprima" ||
                $_GET["ruta"] == "materiaprima-test" ||
                $_GET["ruta"] == "notas-ingresos" ||
                $_GET["ruta"] == "crear-nota-ingreso" ||
                $_GET["ruta"] == "notas-salidas" ||
                $_GET["ruta"] == "crear-nota-salida" ||
                $_GET["ruta"] == "orden-compra" ||
                $_GET["ruta"] == "crear-orden-compra" ||
                $_GET["ruta"] == "editar-orden-compra" ||
                $_GET["ruta"] == "orden-servicio" ||
                $_GET["ruta"] == "crear-orden-servicio" ||
                $_GET["ruta"] == "proveedor" ||
                $_GET["ruta"] == "notas-ingresos-os" ||
                $_GET["ruta"] == "crear-nota-ingreso-os" ||
                $_GET["ruta"] == "kardex" ||
                $_GET["ruta"] == "mp-oc-pendiente" ||
                $_GET["ruta"] == "mp-os-pendiente" ||
                $_GET["ruta"] == "almacen-01" ||
                $_GET["ruta"] == "crear-cuadros-prod" ||
                $_GET["ruta"] == "crear-copas-prod" ||
                $_GET["ruta"] == "tabla-produccion"
            ) {
                // Manejar rutas según la configuración
                $tipoPaginacionMP = (defined('TIPO_PAGINACION_MATERIAPRIMA')) ? TIPO_PAGINACION_MATERIAPRIMA : "cliente";
                
                if ($_GET["ruta"] == "materiaprima") {
                    // Si está en modo "ambos", permitir acceso directo a materiaprima.php
                    // Si está en modo "servidor", redirigir a materiaprima-test.php
                    if ($tipoPaginacionMP === "servidor") {
                        include "modulos/materiaprima/materiaprima-test.php";
                    } else {
                        include "modulos/materiaprima/materiaprima.php";
                    }
                } elseif ($_GET["ruta"] == "materiaprima-test") {
                    // Solo permitir acceso a materiaprima-test si está en modo "servidor" o "ambos"
                    if ($tipoPaginacionMP === "servidor" || $tipoPaginacionMP === "ambos") {
                        include "modulos/materiaprima/materiaprima-test.php";
                    } else {
                        // Si está en modo "cliente", redirigir a materiaprima.php
                        include "modulos/materiaprima/materiaprima.php";
                    }
                } else {
                    include "modulos/materiaprima/" . $_GET["ruta"] . ".php";
                }
            } else if (
                $_GET["ruta"] == "ordencorte" ||
                $_GET["ruta"] == "crear-ordencorte" ||
                $_GET["ruta"] == "editar-ordencorte" ||
                $_GET["ruta"] == "editar-detalle-ordencorte" ||
                $_GET["ruta"] == "almacencorte" ||
                $_GET["ruta"] == "crear-almacencorte" ||
                $_GET["ruta"] == "editar-almacencorte" ||
                $_GET["ruta"] == "editar-almacencorte-lote" ||
                $_GET["ruta"] == "editar-consumo" ||
                $_GET["ruta"] == "editar-almacencorte-mp" ||
                $_GET["ruta"] == "consumo-telas" ||
                $_GET["ruta"] == "urgencias" ||
                $_GET["ruta"] == "urgenciasamp" ||
                $_GET["ruta"] == "en-cortes" ||
                $_GET["ruta"] == "en-taller" ||
                $_GET["ruta"] == "operacion-taller" ||
                $_GET["ruta"] == "asistencia" ||
                $_GET["ruta"] == "ingresos" ||
                $_GET["ruta"] == "crear-ingresos" ||
                $_GET["ruta"] == "crear-ingresos-multi" ||
                $_GET["ruta"] == "crear-segundas-multi" ||
                $_GET["ruta"] == "editar-ingreso" ||
                $_GET["ruta"] == "crear-segunda" ||
                $_GET["ruta"] == "editar-segunda" ||
                $_GET["ruta"] == "en-tallert" ||
                $_GET["ruta"] == "en-tallerp" ||
                $_GET["ruta"] == "marcar-taller" ||
                $_GET["ruta"] == "proyeccion-mp" ||
                $_GET["ruta"] == "produccion-trusas" ||
                $_GET["ruta"] == "produccion-brasier" ||
                $_GET["ruta"] == "produccion-vasco" ||
                $_GET["ruta"] == "quincena" ||
                $_GET["ruta"] == "eficiencia" ||
                $_GET["ruta"] == "eficiencia-global" ||
                $_GET["ruta"] == "pagos" ||
                $_GET["ruta"] == "servicios" ||
                $_GET["ruta"] == "crear-servicio" ||
                $_GET["ruta"] == "editar-servicio" ||
                $_GET["ruta"] == "cierres" ||
                $_GET["ruta"] == "crear-cierre" ||
                $_GET["ruta"] == "editar-cierre" ||
                $_GET["ruta"] == "precio-servicio" ||
                $_GET["ruta"] == "sublimados" ||
                $_GET["ruta"] == "salidas-varios" ||
                $_GET["ruta"] == "crear-salidas-varios" ||
                $_GET["ruta"] == "listar-documento" ||
                $_GET["ruta"] == "pago-servicio" ||
                $_GET["ruta"] == "seguimiento" ||
                $_GET["ruta"] == "enviados-taller" ||
                $_GET["ruta"] == "ajuste-taller" ||
                $_GET["ruta"] == "urgencias-produccion" ||
                $_GET["ruta"] == "urgencias-almacen" ||
                $_GET["ruta"] == "urgencias-corte" ||
                $_GET["ruta"] == "urgencias-plan" ||
                $_GET["ruta"] == "urgencias-maestro" ||
                $_GET["ruta"] == "transferencias-apt" ||
                $_GET["ruta"] == "crear-transferencias-apt" ||
                $_GET["ruta"] == "estampado" ||
                $_GET["ruta"] == "tampografia" ||
                $_GET["ruta"] == "prehormado" ||
                $_GET["ruta"] == "arreglos" ||
                $_GET["ruta"] == "crear-arreglos" ||
                $_GET["ruta"] == "cerrar-arreglos" ||
                $_GET["ruta"] == "en-talleres"
            ) {

                include "modulos/produccion/" . $_GET["ruta"] . ".php";
            } else if (
                $_GET["ruta"] == "tarjetas" ||
                $_GET["ruta"] == "crear-tarjeta" ||
                $_GET["ruta"] == "editar-tarjeta" ||
                $_GET["ruta"] == "copiar-tarjeta" ||
                $_GET["ruta"] == "ficha-tecnica"
            ) {

                include "modulos/tarjetas/" . $_GET["ruta"] . ".php";
            } else if (
                $_GET["ruta"] == "categorias" ||
                $_GET["ruta"] == "productos" ||
                $_GET["ruta"] == "ventas" ||
                $_GET["ruta"] == "crear-venta" ||
                $_GET["ruta"] == "editar-venta"
            ) {

                include "modulos/curso/" . $_GET["ruta"] . ".php";
            } else if (
                $_GET["ruta"] == "contactos" ||
                $_GET["ruta"] == "mailbox" ||
                $_GET["ruta"] == "mensajes"
            ) {

                include "modulos/ticket/" . $_GET["ruta"] . ".php";
            } else if (
                $_GET["ruta"] == "pedidoscv" ||
                $_GET["ruta"] == "pedidos-generados" ||
                $_GET["ruta"] == "pedidos-aprobados" ||
                $_GET["ruta"] == "pedidos-apt" ||
                $_GET["ruta"] == "pedidos-confirmados" ||
                $_GET["ruta"] == "pedidos-facturados" ||
                $_GET["ruta"] == "clientes" ||
                $_GET["ruta"] == "grupos-empresariales" ||
                $_GET["ruta"] == "crear-pedidocv" ||
                $_GET["ruta"] == "escaneo-barcode-pedidocv" ||
                $_GET["ruta"] == "crear-facturascv" ||
                $_GET["ruta"] == "guias-remision" ||
                $_GET["ruta"] == "guias-remision" ||
                $_GET["ruta"] == "facturas" ||
                $_GET["ruta"] == "proformas" ||
                $_GET["ruta"] == "notas-credito" ||
                $_GET["ruta"] == "ver-nota-credito" ||
                $_GET["ruta"] == "editar-nota-credito" ||
                $_GET["ruta"] == "reportes-ventas" ||
                $_GET["ruta"] == "procesar-ce" ||
                $_GET["ruta"] == "boletas" ||
                $_GET["ruta"] == "errores" ||
                $_GET["ruta"] == "cuadre-caja"
            ) {


                include "modulos/facturacion/" . $_GET["ruta"] . ".php";
            } else if ($_GET["ruta"] == "categorias-comerciales") {

                if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "categorias_comerciales")) {
                    denegarAccesoModulo();
                } else {
                    include "modulos/facturacion/categorias-comerciales.php";
                }
            } else if ($_GET["ruta"] == "categorias-por-revisar") {

                if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "categorias_por_revisar")) {
                    denegarAccesoModulo();
                } else {
                    include "modulos/facturacion/categorias-por-revisar.php";
                }
            } else if ($_GET["ruta"] == "zonas-comerciales") {

                if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "zonas_comerciales")) {
                    denegarAccesoModulo();
                } else {
                    include "modulos/zonas-comerciales.php";
                }
            } else if ($_GET["ruta"] == "grupos-marcas") {

                if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "grupos_marcas")) {
                    denegarAccesoModulo();
                } else {
                    include "modulos/grupos-marcas.php";
                }
            } else if ($_GET["ruta"] == "asignacion-grupos-marcas") {

                if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "asignacion_grupos_marcas")) {
                    denegarAccesoModulo();
                } else {
                    include "modulos/asignacion-grupos-marcas.php";
                }
            } else if ($_GET["ruta"] == "mapas-zonas") {

                if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "zonas_comerciales")) {
                    denegarAccesoModulo();
                } else {
                    include "modulos/mapas-zonas.php";
                }
            } else if ($_GET["ruta"] == "rendicion-vasco-caja") {

                if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("vasco_online", "rendicion_cobranzas")) {
                    denegarAccesoModulo();
                } else {
                    include "modulos/cuentas-corrientes/rendicion-vasco-caja.php";
                }
            } else if ($_GET["ruta"] == "gestion-vasco-clientes") {

                if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("vasco_online", "gestion_clientes")) {
                    denegarAccesoModulo();
                } else {
                    include "modulos/cuentas-corrientes/gestion-vasco-clientes.php";
                }
            } else if ($_GET["ruta"] == "solicitudes-atencion-vasco") {

                if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("vasco_online", "solicitudes_atencion")) {
                    denegarAccesoModulo();
                } else {
                    include "modulos/cuentas-corrientes/solicitudes-atencion-vasco.php";
                }
            } else if (
                $_GET["ruta"] == "cuentas" ||
                $_GET["ruta"] == "cuentas-test" ||
                $_GET["ruta"] == "modal-cuentas" ||
                $_GET["ruta"] == "ver-cuentas" ||
                $_GET["ruta"] == "ver-cuentas-consultar" ||
                $_GET["ruta"] == "envio-letras" ||
                $_GET["ruta"] == "ver-envio-letras" ||
                $_GET["ruta"] == "abonos" ||
                $_GET["ruta"] == "cancelar-abonos" ||
                $_GET["ruta"] == "cuentas-pendientes" ||
                $_GET["ruta"] == "cuentas-canceladas" ||
                $_GET["ruta"] == "consultar-cuentas" ||
                $_GET["ruta"] == "reportes-generales" ||
                $_GET["ruta"] == "notificaciones" ||
                $_GET["ruta"] == "letras-plazo-protesto" ||
                $_GET["ruta"] == "credipagos"
            ) {
                // Manejar rutas según la configuración
                $tipoPaginacion = (defined('TIPO_PAGINACION_CUENTAS')) ? TIPO_PAGINACION_CUENTAS : "cliente";
                
                if ($_GET["ruta"] == "cuentas") {
                    // Si está en modo "ambos", permitir acceso directo a cuentas.php
                    // Si está en modo "servidor", redirigir a cuentas-test.php
                    if ($tipoPaginacion === "servidor") {
                        include "modulos/cuentas-corrientes/cuentas-test.php";
                    } else {
                        include "modulos/cuentas-corrientes/cuentas.php";
                    }
                } elseif ($_GET["ruta"] == "cuentas-test") {
                    // Solo permitir acceso a cuentas-test si está en modo "servidor" o "ambos"
                    if ($tipoPaginacion === "servidor" || $tipoPaginacion === "ambos") {
                        include "modulos/cuentas-corrientes/cuentas-test.php";
                    } else {
                        // Si está en modo "cliente", redirigir a cuentas.php
                        include "modulos/cuentas-corrientes/cuentas.php";
                    }
                } else {
                    include "modulos/cuentas-corrientes/" . $_GET["ruta"] . ".php";
                }
            } else if (
                $_GET["ruta"] == "detalleoperaciones" ||
                $_GET["ruta"] == "creardetalleoperaciones" ||
                $_GET["ruta"] == "editardetalleoperaciones"
            ) {

                include "modulos/operaciones/" . $_GET["ruta"] . ".php";
            } else if (
                $_GET["ruta"] == "centro-costos" ||
                $_GET["ruta"] == "gastos-caja" ||
                $_GET["ruta"] == "ingresos-caja" ||
                $_GET["ruta"] == "centro-costos-rsm" ||
                $_GET["ruta"] == "solicitud-caja" ||
                $_GET["ruta"] == "diario" ||
                $_GET["ruta"] == "diario-alerta" ||
                $_GET["ruta"] == "compras-reg" ||
                $_GET["ruta"] == "kardex-carga" ||
                $_GET["ruta"] == "validar-documento" ||
                $_GET["ruta"] == "costos-modelo" ||
                $_GET["ruta"] == "costos-versus"
            ) {

                include "modulos/costos/" . $_GET["ruta"] . ".php";
            } else if (
                $_GET["ruta"] == "equipos" ||
                $_GET["ruta"] == "calendario" ||
                $_GET["ruta"] == "mantenimiento"
            ) {

                include "modulos/mantenimiento/" . $_GET["ruta"] . ".php";
            } else if ($_GET["ruta"] == "linea-tiempo") {

                include "modulos/tiempo/" . $_GET["ruta"] . ".php";
            } else if (
                $_GET["ruta"] == "salir" ||
                $_GET["ruta"] == "reportes"
            ) {

                include "modulos/" . $_GET["ruta"] . ".php";
            } else if (
                $_GET["ruta"] == "leer-stock" ||
                $_GET["ruta"] == "cargas-automaticas"
            ) {

                include "reportes_excel/" . $_GET["ruta"] . ".php";
            } else {

                include "modulos/404.php";
            }
        } else {

            include "modulos/inicio.php";
        }

        /*=============================================
    FOOTER
    =============================================*/

        include "modulos/footer.php";

        echo '</div>';
    } else {

        include "modulos/login.php";
    }

    ?>


    <script src="vistas/js/plantilla.js"></script>
    <script src="vistas/js/usuarios.js"></script>
    <script src="vistas/js/categorias.js"></script>
    <script src="vistas/js/productos.js"></script>
    <script src="vistas/js/clientes.js"></script>
    <script src="vistas/js/grupos-empresariales.js"></script>
    <script src="vistas/js/categorias-comerciales.js?v=4"></script>
    <script src="vistas/js/categorias-por-revisar.js"></script>
    <script src="vistas/js/ventas.js"></script>
    <script src="vistas/js/reportes.js"></script>
    <script src="vistas/js/tipodocumento.js"></script>
    <script src="vistas/js/tipotrabajador.js"></script>

    <script src="vistas/js/articulos.js"></script>
    <script src="vistas/js/marcas.js"></script>
    <script src="vistas/js/colores.js"></script>
    <script src="vistas/js/materiaprima.js"></script>
    <script src="vistas/js/tarjetas.js"></script>
    <script src="vistas/js/movimientos.js"></script>
    <script src="vistas/js/ordencorte.js"></script>
    <script src="vistas/js/urgencias.js"></script>
    <script src="vistas/js/contactos.js"></script>
    <script src="vistas/js/mensajes.js"></script>
    <script src="vistas/js/pedidoscv.js"></script>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "escaneo-barcode-pedidocv") : ?>
        <script src="vistas/js/escaneo-barcode-pedidocv-app.js?v=<?php echo rand(); ?>"></script>
        <script src="vistas/js/crear-pedidocv-barcode.js?v=<?php echo rand(); ?>"></script>
    <?php endif; ?>
    <script src="vistas/js/almacencorte.js"></script>
    <script src="vistas/js/operaciones.js"></script>
    <script src="vistas/js/trabajador.js"></script>
    <script src="vistas/js/modelos.js"></script>
    <script src="vistas/js/cortes.js"></script>
    <script src="vistas/js/talleres.js"></script>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "crear-ingresos-multi") { ?>
    <script src="vistas/js/crear-ingresos-multi.js?v=<?php echo rand(); ?>"></script>
    <?php } ?>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "crear-segundas-multi") { ?>
    <script src="vistas/js/crear-segundas-multi.js?v=<?php echo rand(); ?>"></script>
    <?php } ?>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "dashboard-cobranzas") { ?>
    <script src="vistas/js/dashboard-cobranzas.js?v=31"></script>
    <?php } ?>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "dashboard-decisiones") { ?>
    <script src="vistas/js/dashboard-decisiones.js?v=20"></script>
    <?php } ?>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "historial-credito") { ?>
    <script src="vistas/js/dashboard-decisiones.js?v=20"></script>
    <script src="vistas/js/historial-credito.js?v=10"></script>
    <?php } ?>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "metas-vendedor") { ?>
    <script src="vistas/js/metas-vendedor.js?v=1"></script>
    <?php } ?>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "metas-retos") { ?>
    <script src="vistas/js/metas-retos.js?v=13"></script>
    <?php } ?>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "costos-mensuales-modelo") { ?>
    <script src="vistas/js/costos-modelo-mensual.js?v=2"></script>
    <?php } ?>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "ficha-gerencial-modelos") { ?>
    <script src="vistas/js/ficha-gerencial-modelos.js?v=33"></script>
    <?php } ?>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "resumen-gerencial-modelos") { ?>
    <script src="vistas/js/resumen-gerencial-modelos.js?v=4"></script>
    <?php } ?>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "zonas-comerciales") { ?>
    <script src="vistas/js/zonas-comerciales.js?v=5"></script>
    <?php } ?>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "grupos-marcas") { ?>
    <script src="vistas/js/grupos-marcas.js?v=1"></script>
    <?php } ?>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "asignacion-grupos-marcas") { ?>
    <script src="vistas/js/asignacion-grupos-marcas.js?v=1"></script>
    <?php } ?>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "mapas-zonas") { ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="vistas/js/mapas-zonas.js?v=31"></script>
    <?php } ?>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "linea-credito") { ?>
    <script src="vistas/js/linea-credito.js?v=20"></script>
    <?php } ?>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "descuentos-compuestos") { ?>
    <script src="vistas/js/descuentos-compuestos.js?v=<?php echo rand(); ?>"></script>
    <?php } ?>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "inteligencia-comercial") { ?>
    <script src="vistas/js/inteligencia-comercial.js?v=24"></script>
    <?php } ?>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "sync-vasco") { ?>
    <script src="vistas/js/vasco-online-sync.js?v=<?php echo rand(); ?>"></script>
    <?php } ?>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "rendicion-vasco-caja") { ?>
    <script src="vistas/js/vasco-cobranzas-caja.js?v=<?php echo rand(); ?>"></script>
    <?php } ?>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "gestion-vasco-clientes") { ?>
    <script src="vistas/js/vasco-gestion-cliente.js?v=<?php echo rand(); ?>"></script>
    <?php } ?>
    <?php if (isset($_GET["ruta"]) && $_GET["ruta"] == "solicitudes-atencion-vasco") { ?>
    <script src="vistas/js/vasco-solicitud-atencion.js?v=<?php echo rand(); ?>"></script>
    <?php } ?>
    <script src="vistas/js/sectores.js"></script>
    <script src="vistas/js/paras.js"></script>
    <script src="vistas/js/asistencias.js"></script>
    <script src="vistas/js/produccion.js"></script>
    <script src="vistas/js/agencias.js"></script>
    <script src="vistas/js/tipomovimientos.js"></script>
    <script src="vistas/js/tipopagos.js"></script>
    <script src="vistas/js/condicionesventa.js"></script>
    <script src="vistas/js/unidadesmedida.js"></script>
    <script src="vistas/js/servicios.js"></script>
    <script src="vistas/js/bancos.js"></script>
    <script src="vistas/js/cuentas.js"></script>
    <script src="vistas/js/vendedor.js"></script>
    <script src="vistas/js/facturacion.js"></script>
    <script src="vistas/js/abonos.js"></script>
    <script src="vistas/js/cierres.js"></script>
    <script src="vistas/js/procedimientos.js"></script>
    <script src="vistas/js/salidas.js"></script>
    <script src="vistas/js/proveedor.js"></script>
    <script src="vistas/js/tablamaestra.js"></script>
    <script src="vistas/js/notas-ingresos.js"></script>
    <script src="vistas/js/notas-salidas.js"></script>
    <script src="vistas/js/orden-compra.js"></script>
    <script src="vistas/js/notas-ingresos-os.js"></script>
    <script src="vistas/js/orden-servicio.js"></script>
    <script src="vistas/js/kardex.js"></script>
    <script src="vistas/js/produccion-mp.js"></script>
    <script src="vistas/js/costos.js"></script>
    <script src="vistas/js/gastoscaja.js"></script>
    <script src="vistas/js/ingresoscaja.js"></script>
    <script src="vistas/js/compras.js"></script>
    <script src="vistas/js/mantenimiento.js"></script>
    <script src="vistas/js/transferencia.js"></script>
    <script src="vistas/js/arreglos.js"></script>
    <script src="vistas/js/prehormado.js"></script>


    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Invocamos cada 5 segundos ;)
            const milisegundos = 300 * 1000;
            setInterval(function() {
                // No esperamos la respuesta de la petición porque no nos importa
                fetch("vistas/modulos/refrescar.php");
            }, milisegundos);
        });
    </script>

</body>

</html>