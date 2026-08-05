<?php

$documentoIngreso = isset($_GET["idIngreso"]) ? $_GET["idIngreso"] : null;
$ingreso = $documentoIngreso
  ? ControladorIngresos::ctrMostrarIngresos("documento", $documentoIngreso)
  : false;

if (!$ingreso) {
  echo '<script>
    swal({
      type: "error",
      title: "Error",
      text: "¡No se encontró el ingreso!",
      showConfirmButton: true,
      confirmButtonText: "Cerrar"
    }).then((result)=>{
      if(result.value){ window.location="ingresos"; }
    });
  </script>';
  return;
}

$detalleIngreso = ModeloIngresos::editarDetalleIngreso($documentoIngreso);
$totalItems = is_array($detalleIngreso) ? count($detalleIngreso) : 0;
$modelosIngreso = array();
if ($totalItems > 0) {
  foreach ($detalleIngreso as $filaDetalle) {
    if (!empty($filaDetalle["modelo"])) {
      $modelosIngreso[$filaDetalle["modelo"]] = $filaDetalle["modelo"];
    }
  }
  ksort($modelosIngreso);
}
$fechaIngreso = !empty($ingreso["fecha"]) ? substr($ingreso["fecha"], 0, 10) : "—";
$tallerCod = isset($ingreso["taller"]) ? $ingreso["taller"] : "";
$nombreSector = trim($tallerCod . (!empty($ingreso["nom_sector"]) ? " - " . $ingreso["nom_sector"] : ""));
$nombreUsuario = !empty($ingreso["nombre"]) ? $ingreso["nombre"] : "—";
$guiaIngreso = isset($ingreso["guia"]) && $ingreso["guia"] !== "" ? $ingreso["guia"] : "—";
$totalUnidades = isset($ingreso["total"]) ? $ingreso["total"] : 0;
$sectorUrl = isset($_GET["sector"]) ? $_GET["sector"] : $tallerCod;

?>

<style>
  .ingreso-summary {
    background: #f3f8fd;
    border: 1px solid #d5e6f5;
    border-radius: 3px;
    padding: 8px 12px;
    margin-bottom: 12px;
  }
  .ingreso-summary-top {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 6px 12px;
    margin-bottom: 8px;
  }
  .ingreso-summary-title {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
    color: #333;
  }
  .ingreso-summary-title small {
    margin-left: 8px;
    font-size: 12px;
    font-weight: 400;
    color: #888;
  }
  .ingreso-summary-badge {
    font-size: 11px;
    padding: 3px 7px;
  }
  .ingreso-summary-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 6px 14px;
  }
  .ingreso-summary-card {
    display: inline-flex;
    align-items: baseline;
    gap: 5px;
    background: transparent;
    border: 0;
    padding: 0;
    min-height: 0;
  }
  .ingreso-summary-card .k {
    font-size: 11px;
    text-transform: uppercase;
    color: #7a94ab;
  }
  .ingreso-summary-card .k:after {
    content: ":";
  }
  .ingreso-summary-card .v {
    font-size: 12px;
    font-weight: 600;
    color: #333;
  }
  .ingreso-summary-card.ingreso-guia-edit {
    align-items: center;
  }
  .ingreso-guia-edit .ingreso-guia-controls {
    display: inline-flex;
    align-items: center;
    gap: 4px;
  }
  .ingreso-guia-edit #editarGuiaIngreso {
    height: 26px;
    padding: 2px 6px;
    font-size: 12px;
    font-weight: 600;
    width: 110px;
  }
  .ingreso-guia-edit .btn {
    padding: 2px 7px;
    line-height: 1.4;
  }
  .ingreso-dt-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    margin-bottom: 8px;
  }
  .ingreso-dt-toolbar .ingreso-filtro-modelo {
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 0;
    order: 2;
  }
  .ingreso-dt-toolbar .ingreso-filtro-modelo > label {
    margin: 0;
    font-weight: 600;
    white-space: nowrap;
  }
  .ingreso-dt-toolbar .ingreso-filtro-modelo .bootstrap-select,
  .ingreso-dt-toolbar .ingreso-filtro-modelo .form-control {
    width: 200px !important;
    max-width: 200px;
  }
  .ingreso-dt-toolbar .dataTables_filter {
    margin: 0;
    float: none !important;
    text-align: left;
    order: 1;
  }
  .ingreso-dt-toolbar .dataTables_filter label {
    margin: 0;
  }
</style>

<div class="content-wrapper">
  <section class="content-header">
    <h1>
      Editar ingreso
      <small><?php echo htmlspecialchars($ingreso["documento"]); ?></small>
    </h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
      <li><a href="ingresos">Ingresos</a></li>
      <li class="active">Editar</li>
    </ol>
  </section>

  <section class="content">

    <div class="ingreso-summary">
      <div class="ingreso-summary-top">
        <h3 class="ingreso-summary-title">
          Ingreso <?php echo htmlspecialchars($ingreso["documento"]); ?>
          <small>Edita cantidades o elimina líneas</small>
        </h3>
        <span class="label label-primary ingreso-summary-badge">Stock</span>
      </div>
      <div class="ingreso-summary-grid">
        <div class="ingreso-summary-card ingreso-guia-edit">
          <span class="k">Guía</span>
          <span class="ingreso-guia-controls">
            <input type="text"
                   class="form-control input-sm"
                   id="editarGuiaIngreso"
                   value="<?php echo htmlspecialchars($guiaIngreso === "—" ? "" : $guiaIngreso); ?>"
                   placeholder="N° guía"
                   maxlength="30">
            <button type="button" class="btn btn-primary btn-xs" id="btnGuardarGuiaIngreso" title="Guardar guía">
              <i class="fa fa-save"></i>
            </button>
          </span>
        </div>
        <div class="ingreso-summary-card">
          <span class="k">Taller</span>
          <span class="v"><?php echo htmlspecialchars($nombreSector !== "" ? $nombreSector : "—"); ?></span>
        </div>
        <div class="ingreso-summary-card">
          <span class="k">Fecha</span>
          <span class="v"><?php echo htmlspecialchars($fechaIngreso); ?></span>
        </div>
        <div class="ingreso-summary-card">
          <span class="k">Usuario</span>
          <span class="v"><?php echo htmlspecialchars($nombreUsuario); ?></span>
        </div>
        <div class="ingreso-summary-card">
          <span class="k">Ítems</span>
          <span class="v"><?php echo (int)$totalItems; ?></span>
        </div>
        <div class="ingreso-summary-card">
          <span class="k">Total</span>
          <span class="v"><?php echo htmlspecialchars($totalUnidades); ?></span>
        </div>
      </div>
    </div>

    <div class="box box-primary">
      <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-list"></i> Detalle del ingreso</h3>
        <div class="box-tools pull-right">
          <a href="ingresos" class="btn btn-default btn-sm">
            <i class="fa fa-arrow-left"></i> Volver
          </a>
        </div>
      </div>

      <div class="box-body">
        <input type="hidden" value="<?= $_SESSION["perfil"]; ?>" id="perfilOculto">
        <input type="hidden" value="<?= htmlspecialchars($documentoIngreso); ?>" id="codigoIngreso">
        <input type="hidden" value="<?= htmlspecialchars($sectorUrl); ?>" id="sectorIngreso">

        <div id="filtroModeloIngresoWrap" class="ingreso-filtro-modelo" style="display:none">
          <label for="filtroModeloIngreso">Modelo</label>
          <select class="form-control selectpicker" id="filtroModeloIngreso" data-live-search="true" data-size="8" title="Todos los modelos">
            <option value="">Todos los modelos</option>
            <?php foreach ($modelosIngreso as $modeloFiltro) { ?>
              <option value="<?= htmlspecialchars($modeloFiltro); ?>"><?= htmlspecialchars($modeloFiltro); ?></option>
            <?php } ?>
          </select>
        </div>

        <table class="table table-bordered table-striped dt-responsive tablaEditarDetalleIngreso" width="100%">
          <thead>
            <tr>
              <th>N°</th>
              <th class="text-center">Articulo</th>
              <th class="text-center">Modelo</th>
              <th class="text-center">Nombre</th>
              <th class="text-center">Color</th>
              <th class="text-center">Talla</th>
              <th class="text-center">Marca</th>
              <th>
                <center>Cantidad Total</center>
              </th>
              <th class="text-center">Saldo</th>
              <th style="width: 150px">Acciones</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </section>
</div>

<div id="modalEditarDetalleIngreso" class="modal fade" role="dialog">

  <div class="modal-dialog" style="width:50%">

    <div class="modal-content">

      <form role="form" method="post">

        <div class="modal-header" style="background:#3c8dbc; color:white">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Editar cantidad del ingreso</h4>
        </div>

        <div class="modal-body">
          <div class="box-body">
            <div class="form-group">

              <input type="hidden" class="form-control input-md" name="cantidadO" id="cantidadO">
              <input type="hidden" class="form-control input-md" name="saldoO" id="saldoO">
              <input type="hidden" class="form-control input-md" name="codigo" id="codigo">
              <input type="hidden" class="form-control input-md" name="sector" id="sector">
              <input type="hidden" class="form-control input-md" name="idcierre" id="idcierre">
              <input type="hidden" class="form-control input-md" name="almacen" id="almacen">

              <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">Artículo</label>
              <div class="col-lg-2">
                <input type="text" class="form-control input-md" name="articulo" id="articulo" readonly required>
              </div>

              <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">Modelo</label>
              <div class="col-lg-2">
                <input type="text" class="form-control input-md" name="modelo" id="modelo" readonly required>
              </div>

              <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">Nombre</label>
              <div class="col-lg-5">
                <input type="text" class="form-control input-md" name="nombre" id="nombre" readonly required>
              </div>

              <div class="col-lg-12"></div>

              <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">Color</label>
              <div class="col-lg-2">
                <input type="text" class="form-control input-md" name="color" id="color" readonly required>
              </div>

              <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">Talla</label>
              <div class="col-lg-2">
                <input type="text" class="form-control input-md" name="talla" id="talla" readonly required>
              </div>

              <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">Cantidad</label>
              <div class="col-lg-2">
                <input type="number" class="form-control input-md" min="1" name="cantidad" id="cantidad" required>
              </div>

              <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">Saldo</label>
              <div class="col-lg-2">
                <input type="number" class="form-control input-md" name="saldo" id="saldo" required readonly>
              </div>

            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
          <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>

        <?php
        $editarDetalle = new ControladorIngresos();
        $editarDetalle->ctrEditarIngresoB();
        ?>

      </form>

    </div>

  </div>

</div>

<script>
  window.document.title = "Editar ingreso";
</script>
