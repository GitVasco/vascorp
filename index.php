<?php
/*=============================================
CORS
=============================================*/

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: POST');
/* 
* CONTROLADORES
*/

require_once "controladores/plantilla.controlador.php";

require_once "controladores/usuarios.controlador.php";

require_once "controladores/categorias.controlador.php";

require_once "controladores/productos.controlador.php";

require_once "controladores/clientes.controlador.php";

require_once "controladores/grupos-empresariales.controlador.php";

require_once "controladores/zonas-comerciales.controlador.php";

require_once "controladores/grupos-marcas-comercial.controlador.php";

require_once "controladores/metas-retos.config.php";

require_once "controladores/metas-retos.controlador.php";

require_once "controladores/costos-modelo-mensual.controlador.php";

require_once "controladores/ficha-gerencial-modelos.controlador.php";

require_once "controladores/metricas-comerciales.controlador.php";

require_once "controladores/categorias-clientes.controlador.php";

require_once "controladores/ventas.controlador.php";

require_once "controladores/marcas.controlador.php";

require_once "controladores/colores.controlador.php";

require_once "controladores/abonos.controlador.php";

require_once "controladores/agencia.controlador.php";

require_once "controladores/articulos.controlador.php";

require_once "controladores/asistencia.controlador.php";

require_once "controladores/bancos.controlador.php";

require_once "controladores/cierres.controlador.php";

require_once "controladores/condicionventa.controlador.php";

require_once "controladores/cuentas.controlador.php";

require_once "controladores/materiaprima.controlador.php";

require_once "controladores/tarjetas.controlador.php";

require_once "controladores/movimientos.controlador.php";

require_once "controladores/ordencorte.controlador.php";

require_once "controladores/contactos.controlador.php";

require_once "controladores/mensajes.controlador.php";

require_once "controladores/ingresos.controlador.php";

require_once "controladores/ingresos-multi.controlador.php";

require_once "controladores/ingresos-segunda-multi.controlador.php";

require_once "controladores/modelos.controlador.php";

require_once "controladores/pedidos.controlador.php";

require_once "controladores/operaciones.controlador.php";

require_once "controladores/paras.controlador.php";

require_once "controladores/tipodocumento.controlador.php";

require_once "controladores/almacencorte.controlador.php";

require_once "controladores/tipotrabajador.controlador.php";

require_once "controladores/trabajador.controlador.php";

require_once "controladores/cortes.controlador.php";

require_once "controladores/talleres.controlador.php";

require_once "controladores/sectores.controlador.php";

require_once "controladores/produccion.controlador.php";

require_once "controladores/talonarios.controlador.php";

require_once "controladores/facturacion.controlador.php";

require_once "controladores/procedimiento.controlador.php";

require_once "controladores/salidas.controlador.php";

require_once "controladores/proveedor.controlador.php";

require_once "controladores/maestras.controlador.php";

require_once "controladores/notas-ingresos.controlador.php";

require_once "controladores/notas-salidas.controlador.php";

require_once "controladores/orden-compra.controlador.php";

require_once "controladores/orden-servicio.controlador.php";

require_once "controladores/servicio.controlador.php";

require_once "controladores/tipomovimiento.controlador.php";

require_once "controladores/tipopago.controlador.php";

require_once "controladores/unidadmedida.controlador.php";

require_once "controladores/vendedor.controlador.php";

require_once "controladores/dashboard-cobranzas.controlador.php";

require_once "controladores/dashboard-cxc.config.php";

require_once "controladores/dashboard-cxc.controlador.php";

require_once "controladores/dashboard-decisiones.controlador.php";

require_once "controladores/decisiones-credito.config.php";

require_once "controladores/decisiones-credito.controlador.php";

require_once "controladores/linea-credito.controlador.php";

require_once "controladores/metas-vendedor.controlador.php";

require_once "controladores/descuentos-compuestos.controlador.php";

require_once "controladores/inteligencia-comercial.config.php";

require_once "controladores/inteligencia-comercial.controlador.php";

require_once "controladores/centro-costos.controlador.php";

require_once "controladores/compras.controlador.php";

require_once "controladores/mantenimiento.controlador.php";

require_once "controladores/contabilidad.controlador.php";

require_once "controladores/evaluacion.controlador.php";

require_once "controladores/transferencias.controlador.php";

require_once "controladores/arreglos.controlador.php";

require_once "controladores/vasco-sync.controlador.php";

require_once "controladores/config.php";

require_once "controladores/permisos-modulos.config.php";

require_once "controladores/vasco-online.config.php";

/* 
* MODELOS
*/
require_once "modelos/usuarios.modelo.php";

require_once "modelos/categorias.modelo.php";

require_once "modelos/productos.modelo.php";

require_once "modelos/clientes.modelo.php";

require_once "modelos/grupos-empresariales.modelo.php";

require_once "modelos/zonas-comerciales.modelo.php";

require_once "modelos/grupos-marcas-comercial.modelo.php";

require_once "modelos/metas-retos.modelo.php";

require_once "modelos/costos-modelo-mensual.modelo.php";

require_once "modelos/ficha-gerencial-modelos.modelo.php";

require_once "modelos/metricas-comerciales.modelo.php";

require_once "modelos/categorias-clientes.modelo.php";

require_once "modelos/ventas.modelo.php";

require_once "modelos/marcas.modelo.php";

require_once "modelos/colores.modelo.php";

require_once "modelos/articulos.modelo.php";

require_once "modelos/materiaprima.modelo.php";

require_once "modelos/tarjetas.modelo.php";

require_once "modelos/movimientos.modelo.php";

require_once "modelos/ordencorte.modelo.php";

require_once "modelos/contactos.modelo.php";

require_once "modelos/mensajes.modelo.php";

require_once "modelos/pedidos.modelo.php";

require_once "modelos/operaciones.modelo.php";

require_once "modelos/tipodocumento.modelo.php";

require_once "modelos/almacencorte.modelo.php";

require_once "modelos/tipotrabajador.modelo.php";

require_once "modelos/trabajador.modelo.php";

require_once "modelos/modelos.modelo.php";

require_once "modelos/cortes.modelo.php";

require_once "modelos/talleres.modelo.php";

require_once "modelos/sectores.modelo.php";

require_once "modelos/paras.modelo.php";

require_once "modelos/asistencia.modelo.php";

require_once "modelos/ingresos.modelo.php";

require_once "modelos/agencia.modelo.php";

require_once "modelos/tipomovimiento.modelo.php";

require_once "modelos/tipopago.modelo.php";

require_once "modelos/unidadmedida.modelo.php";

require_once "modelos/condicionventa.modelo.php";

require_once "modelos/servicio.modelo.php";

require_once "modelos/bancos.modelo.php";

require_once "modelos/cuentas.modelo.php";

require_once "modelos/dashboard-cobranzas.modelo.php";

require_once "modelos/dashboard-cxc.modelo.php";

require_once "modelos/dashboard-decisiones.modelo.php";

require_once "modelos/decisiones-credito.modelo.php";

require_once "modelos/linea-credito.modelo.php";

require_once "modelos/metas-vendedor.modelo.php";

require_once "modelos/descuentos-compuestos.modelo.php";

require_once "modelos/inteligencia-comercial.modelo.php";

require_once "modelos/vendedor.modelo.php";

require_once "modelos/abonos.modelo.php";

require_once "modelos/cierres.modelo.php";

require_once "modelos/produccion.modelo.php";

require_once "modelos/talonarios.modelo.php";

require_once "modelos/facturacion.modelo.php";

require_once "modelos/procedimiento.modelo.php";

require_once "modelos/salidas.modelo.php";

require_once "modelos/proveedor.modelo.php";

require_once "modelos/maestras.modelo.php";

require_once "modelos/notas-ingresos.modelo.php";

require_once "modelos/notas-salidas.modelo.php";

require_once "modelos/orden-compra.modelo.php";

require_once "modelos/orden-servicio.modelo.php";

require_once "modelos/centro-costos.modelo.php";

require_once "modelos/compras.modelo.php";

require_once "modelos/mantenimiento.modelo.php";

require_once "modelos/contabilidad.modelo.php";

require_once "modelos/evaluacion.modelo.php";

require_once "modelos/transferencias.modelo.php";

require_once "modelos/arreglos.modelo.php";

require_once "modelos/vasco-sync.modelo.php";

require_once "extensiones/vendor/autoload.php";

$plantilla = new ControladorPlantilla();
$plantilla->ctrPlantilla();
