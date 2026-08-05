<?php

$item = "codigo";
$valor = isset($_GET["idCierre"]) ? $_GET["idCierre"] : null;
$venta = $valor ? ControladorCierres::ctrMostrarCierres($item, $valor) : false;

if (!$venta) {
  echo '<script>
    swal({
      type: "error",
      title: "Error",
      text: "¡No se encontró el cierre!",
      showConfirmButton: true,
      confirmButtonText: "Cerrar"
    }).then((result)=>{
      if(result.value){ window.location="cierres"; }
    });
  </script>';
  return;
}

if ($venta["estado_pago"] == "PAGADO") {
  echo '<script>
    swal({
      type: "warning",
      title: "Cierre pagado",
      text: "No se puede editar un cierre con estado PAGADO.",
      showConfirmButton: true,
      confirmButtonText: "Volver"
    }).then((result)=>{
      if(result.value){ window.location="cierres"; }
    });
  </script>';
  return;
}

$itemUsuario = "id";
$valorUsuario = $venta["usuario"];
$vendedor = ControladorUsuarios::ctrMostrarUsuarios($itemUsuario, $valorUsuario);

$valorSector = $venta["taller"];
$sector = ControladorSectores::ctrMostrarSectores($valorSector);
$nombreSector = $sector["cod_sector"] . " - " . $sector["nom_sector"];
$fechaCierre = substr($venta["fecha"], 0, 10);
$listaProductos = ControladorCierres::ctrMostrarDetallesCierres("codigo", $_GET["idCierre"]);

?>

<style>
  .cierre-summary {
    background: linear-gradient(135deg, #fff8e8 0%, #ffffff 55%);
    border: 1px solid #f0e0b8;
    border-radius: 4px;
    padding: 14px 16px;
    margin-bottom: 16px;
  }
  .cierre-summary-top {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 12px;
  }
  .cierre-summary-title {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #333;
  }
  .cierre-summary-title small {
    display: block;
    margin-top: 2px;
    font-size: 12px;
    font-weight: 400;
    color: #888;
  }
  .cierre-summary-badge {
    font-size: 12px;
    padding: 6px 10px;
    border-radius: 3px;
  }
  .cierre-summary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
  }
  @media (min-width: 992px) {
    .cierre-summary-grid {
      grid-template-columns: repeat(4, minmax(0, 1fr));
    }
  }
  .cierre-summary-card {
    background: #fff;
    border: 1px solid #eee3c8;
    border-radius: 3px;
    padding: 8px 10px;
    min-height: 58px;
  }
  .cierre-summary-card .k {
    display: block;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .03em;
    color: #9a8b63;
    margin-bottom: 3px;
  }
  .cierre-summary-card .v {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #333;
    word-break: break-word;
  }
  .cierre-fields-row .form-group {
    margin-bottom: 12px;
  }
  .cierre-items-head {
    background: #f4f6f9;
    border: 1px solid #e5e5e5;
    border-radius: 3px 3px 0 0;
    padding: 8px 12px;
    margin: 0;
    font-size: 12px;
    font-weight: 600;
    color: #555;
  }
  .nuevoCierres {
    border: 1px solid #e5e5e5;
    border-top: 0;
    border-radius: 0 0 3px 3px;
    min-height: 220px;
    max-height: 420px;
    overflow-y: auto;
    overflow-x: hidden;
    background: #fff;
    padding: 6px 0;
  }
  .munditoCierre {
    padding: 8px 12px !important;
    margin: 0 !important;
    border-bottom: 1px solid #f0f0f0;
  }
  .munditoCierre:hover {
    background: #fafafa;
  }
  .cierre-empty-state {
    display: none;
    text-align: center;
    padding: 48px 20px;
    color: #888;
  }
  .cierre-empty-state i {
    font-size: 28px;
    display: block;
    margin-bottom: 10px;
    color: #bbb;
  }
  .cierre-total-box {
    background: #f9fafb;
    border: 1px solid #e5e5e5;
    border-radius: 3px;
    padding: 12px 14px;
  }
  .cierre-total-box .help-block {
    margin: 0 0 6px;
    font-size: 12px;
  }
  .cierre-hint {
    margin-top: 10px;
    font-size: 12px;
    color: #777;
  }
  .box-articulos-cierre .box-header h3 {
    font-size: 16px;
    margin: 0;
  }
</style>

<div class="content-wrapper">

  <section class="content-header">
    <h1>
      Editar cierre
      <small><?php echo htmlspecialchars($venta["codigo"]); ?></small>
    </h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
      <li><a href="cierres">Cierres</a></li>
      <li class="active">Editar</li>
    </ol>
  </section>

  <section class="content">

    <div class="cierre-summary">
      <div class="cierre-summary-top">
        <h3 class="cierre-summary-title">
          Cierre <?php echo htmlspecialchars($venta["codigo"]); ?>
          <small>Edita guía, cantidades o quita/agrega artículos</small>
        </h3>
        <span class="label label-warning cierre-summary-badge">POR PAGAR · editable</span>
      </div>
      <div class="cierre-summary-grid">
        <div class="cierre-summary-card">
          <span class="k">Guía</span>
          <span class="v"><?php echo htmlspecialchars($venta["guia"] !== "" ? $venta["guia"] : "—"); ?></span>
        </div>
        <div class="cierre-summary-card">
          <span class="k">Taller</span>
          <span class="v"><?php echo htmlspecialchars($nombreSector); ?></span>
        </div>
        <div class="cierre-summary-card">
          <span class="k">Fecha</span>
          <span class="v"><?php echo htmlspecialchars($fechaCierre); ?></span>
        </div>
        <div class="cierre-summary-card">
          <span class="k">Usuario</span>
          <span class="v"><?php echo htmlspecialchars($vendedor["nombre"]); ?></span>
        </div>
        <div class="cierre-summary-card">
          <span class="k">Ítems</span>
          <span class="v" id="cierreCountItems"><?php echo count($listaProductos); ?></span>
        </div>
        <div class="cierre-summary-card">
          <span class="k">Total unidades</span>
          <span class="v" id="cierreSummaryTotal"><?php echo htmlspecialchars($venta["total"]); ?></span>
        </div>
      </div>
    </div>

    <div class="row">

      <!--=====================================
      FORMULARIO
      ======================================-->
      <div class="col-lg-5 col-xs-12">

        <div class="box box-warning">

          <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-pencil"></i> Datos del cierre</h3>
          </div>

          <form role="form" method="post" class="formularioCierre" id="formEditarCierre">

            <div class="box-body">

              <input type="hidden" name="idVendedor" value="<?php echo htmlspecialchars($vendedor["id"]); ?>">
              <input type="hidden" id="nuevaVenta" name="editarCierre" value="<?php echo htmlspecialchars($venta["codigo"]); ?>">
              <input type="hidden" name="idSectorVenta" id="idSectorVenta" value="<?php echo htmlspecialchars($venta["taller"]); ?>">

              <div class="row cierre-fields-row">
                <div class="col-sm-6">
                  <div class="form-group">
                    <label>N° Guía</label>
                    <div class="input-group">
                      <span class="input-group-addon"><i class="fa fa-file-text-o"></i></span>
                      <input type="text" class="form-control" name="editarGuia" value="<?php echo htmlspecialchars($venta["guia"]); ?>" placeholder="Número de guía" required>
                    </div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="form-group">
                    <label>Taller</label>
                    <div class="input-group">
                      <span class="input-group-addon"><i class="fa fa-industry"></i></span>
                      <input type="text" class="form-control" id="editarSectorVenta" value="<?php echo htmlspecialchars($nombreSector); ?>" readonly>
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-group buscador" id="elid" style="margin-bottom: 8px;">
                <label>Buscar en el cierre</label>
                <div class="input-group">
                  <input type="text" class="form-control" id="buscadorCierre" name="buscadorCierre" placeholder="Filtrar artículo del listado...">
                  <span class="input-group-addon"><i class="fa fa-search"></i></span>
                </div>
              </div>

              <div class="row cierre-items-head">
                <div class="col-xs-3">Servicio</div>
                <div class="col-xs-5">Artículo</div>
                <div class="col-xs-2">Cantidad</div>
                <div class="col-xs-2">Disponible</div>
              </div>

              <div class="form-group row nuevoCierres">

                <div class="cierre-empty-state" id="cierreEmptyState">
                  <i class="fa fa-inbox"></i>
                  No hay artículos en este cierre.<br>
                  Agrégalos desde la tabla de la derecha.
                </div>

                <?php

                foreach ($listaProductos as $key => $value) {

                  $infoProducto = controladorArticulos::ctrMostrarArticulos($value["articulo"]);
                  $detaServicios = ControladorServicios::ctrMostrarDetallesServicioUnico("id", $value["cod_servicio"]);

                  $saldoActual = isset($detaServicios["saldo"]) ? (int)$detaServicios["saldo"] : 0;
                  $cantidadActual = (int)$value["cantidad"];
                  $disponible = $saldoActual + $cantidadActual;
                  $restante = $disponible - $cantidadActual;
                  $codigoServicioCab = isset($detaServicios["codigo"]) ? $detaServicios["codigo"] : "";

                  echo '<div class="row munditoCierre">
                  <div class="col-xs-3">
                    <div class="input-group">
                      <span class="input-group-addon"><button type="button" class="btn btn-danger btn-xs quitarServicio" title="Quitar del cierre" articuloCierre="' . htmlspecialchars($infoProducto["articulo"]) . '" codigoServicio="' . htmlspecialchars($value["cod_servicio"]) . '"><i class="fa fa-times"></i></button></span>
                      <input type="text" class="form-control nuevoCodServicio2" name="agregarProducto" value="' . htmlspecialchars($codigoServicioCab) . '" readonly required>
                      <input type="hidden" class="form-control nuevoCodServicio" name="agregarProducto" value="' . htmlspecialchars($value["cod_servicio"]) . '">
                    </div>
                  </div>

                  <div class="col-xs-5" style="padding-right:0px">
                      <input type="text" class="form-control nuevaDescripcionProducto" articuloCierre="' . htmlspecialchars($infoProducto["articulo"]) . '" name="agregarProducto" value="' . htmlspecialchars($infoProducto["packing"]) . '" codigoP="' . htmlspecialchars($infoProducto["articulo"]) . '" saldo="' . $disponible . '" readonly required>
                  </div>

                  <div class="col-xs-2">
                    <input type="number" class="form-control nuevaCantidadProducto" name="nuevaCantidadProducto" min="1" value="' . $cantidadActual . '" servicio="' . $disponible . '" nuevoServicio="' . $restante . '" required>
                  </div>

                  <div class="col-xs-2 ingresoServicio">
                    <input type="number" class="form-control nuevoServicioProducto" name="nuevoServicioProducto" min="0" value="' . $restante . '" readonly title="Saldo que quedará disponible">
                  </div>
                </div>';
                }

                ?>

              </div>

              <p class="cierre-hint">
                <i class="fa fa-info-circle"></i>
                Puedes cambiar cantidades, quitar líneas o agregar más desde la derecha. No se puede dejar el cierre vacío.
              </p>

              <input type="hidden" id="listaProductos" name="listaProductos">

              <button type="button" class="btn btn-default hidden-lg btnAgregarProducto">Agregar artículo</button>

              <hr>

              <div class="cierre-total-box">
                <p class="help-block">Total unidades</p>
                <div class="input-group">
                  <span class="input-group-addon"><i class="fa fa-cubes"></i></span>
                  <input type="text" class="form-control input-lg" id="nuevoTotalVenta" name="nuevoTotalVenta" value="<?php echo htmlspecialchars($venta["total"]); ?>" readonly required>
                  <input type="hidden" name="totalVenta" value="<?php echo htmlspecialchars($venta["total"]); ?>" id="totalVenta">
                </div>
              </div>

            </div>

            <div class="box-footer">
              <a href="cierres" class="btn btn-default"><i class="fa fa-arrow-left"></i> Volver</a>
              <button type="submit" class="btn btn-primary pull-right" id="btnGuardarEditarCierre">
                <i class="fa fa-save"></i> Guardar cambios
              </button>
            </div>

          </form>

          <?php
          $editarCierre = new ControladorCierres();
          $editarCierre->ctrEditarCierres();
          ?>

        </div>

      </div>

      <!--=====================================
      TABLA DE ARTÍCULOS DISPONIBLES
      ======================================-->
      <div class="col-lg-7 hidden-md hidden-sm hidden-xs">

        <div class="box box-success box-articulos-cierre">

          <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-plus-circle"></i> Agregar artículos del taller</h3>
          </div>

          <div class="box-body">
            <p class="text-muted" style="margin-top:0">
              Solo se listan servicios con saldo del taller <strong><?php echo htmlspecialchars($nombreSector); ?></strong>.
            </p>

            <table class="table table-bordered table-striped dt-responsive tablaArticuloCierre" width="100%">
              <thead>
                <tr>
                  <th>Codigo</th>
                  <th>Articulo</th>
                  <th>Modelo</th>
                  <th>Nombre</th>
                  <th>Color</th>
                  <th>Talla</th>
                  <th>Saldo</th>
                  <th>Cerrar</th>
                  <th>Acciones</th>
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
window.document.title = "Editar cierre";
localStorage.setItem("sectorCierre", <?php echo json_encode((string)$venta["taller"]); ?>);
</script>

<script>
(function () {
  function actualizarEstadoVacioCierre() {
    var hayItems = $(".nuevoCierres .munditoCierre").length > 0;
    $("#cierreEmptyState").toggle(!hayItems);
    $("#cierreCountItems").text($(".nuevoCierres .munditoCierre").length);
  }

  // Esperar a que cierres.js cargue (va al final del body)
  $(window).on("load", function () {
    actualizarEstadoVacioCierre();
    if (typeof listarCierres === "function") {
      listarCierres();
    }
    if (typeof sumarTotalCierre === "function") {
      sumarTotalCierre();
    }
    if (typeof quitarAgregarProducto === "function") {
      quitarAgregarProducto();
    }

    $(".formularioCierre").on("click", "button.quitarServicio", function () {
      setTimeout(actualizarEstadoVacioCierre, 0);
    });

    $(".tablaArticuloCierre tbody").on("click", "button.agregarServicio", function () {
      setTimeout(actualizarEstadoVacioCierre, 300);
    });

    $("#formEditarCierre").on("submit", function (e) {
      if (typeof listarCierres === "function") {
        listarCierres();
      }

      var items = $(".nuevoCierres .munditoCierre").length;
      if (items === 0) {
        e.preventDefault();
        swal({
          type: "warning",
          title: "Sin artículos",
          text: "Debe dejar al menos un artículo. Si quiere quitarlos todos, elimine el cierre.",
          confirmButtonText: "Entendido",
        });
        return false;
      }

      var invalido = false;
      $(".nuevaCantidadProducto").each(function () {
        if (Number($(this).val()) <= 0) {
          invalido = true;
        }
      });

      if (invalido) {
        e.preventDefault();
        swal({
          type: "warning",
          title: "Cantidad inválida",
          text: "Todas las cantidades deben ser mayores a cero.",
          confirmButtonText: "Entendido",
        });
        return false;
      }
    });
  });
})();
</script>

<script>
$('.nuevoCierres').ready(function () {
  $('#buscadorCierre').keyup(function () {
    var nombres = $('.nuevaDescripcionProducto');
    var buscando = $(this).val();
    var item = '';

    for (var i = 0; i < nombres.length; i++) {
      item = $(nombres[i]).val() || '';
      var item2 = item.toLowerCase();

      if (buscando.length == 0 || item.indexOf(buscando) > -1 || item2.indexOf(buscando.toLowerCase()) > -1) {
        $(nombres[i]).parents('.munditoCierre').show();
      } else {
        $(nombres[i]).parents('.munditoCierre').hide();
      }
    }
  });
});
</script>
