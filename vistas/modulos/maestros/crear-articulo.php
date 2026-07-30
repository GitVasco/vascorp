<div class="content-wrapper crear-articulo-page">

  <section class="content-header">
    <h1>Agregar color / talla</h1>
    <ol class="breadcrumb">
      <li><a href="modelosjf"><i class="fa fa-tag"></i> Modelos</a></li>
      <li class="active">Agregar color / talla</li>
    </ol>
  </section>

  <section class="content">

    <?php
      $item = "modelo";
      $valor = isset($_GET["modelo"]) ? $_GET["modelo"] : "";
      $_SESSION["modelos"] = $valor;
      $modelo = ControladorModelos::ctrMostrarModelos($item, $valor);
      $variantes = ControladorModelos::ctrMostrarVariantesModelo($valor);
      $coloresExistentes = isset($variantes["colores"]) ? $variantes["colores"] : array();
      $tallasExistentes = isset($variantes["tallas"]) ? $variantes["tallas"] : array();
      $chkDesc = !empty($modelo["descuentos"]) ? "checked" : "";
      $chkPrecios = !empty($modelo["precios"]) ? "checked" : "";
      $chkEfectosDesc = !empty($modelo["efectos_desc"]) ? "checked" : "";
      $chkEfectosIGV = !empty($modelo["efectos_igv"]) ? "checked" : "";
      $codUnidad = ControladorModelos::ctrCodUnidadModelo($modelo);
      $unidadLabel = $codUnidad;
      $unidadCat = ControladorUnidadMedidas::ctrMostrarUnidadMedidas("codigo", $codUnidad);
      if (!empty($unidadCat["descripcion"])) {
        $unidadLabel = $codUnidad . " — " . $unidadCat["descripcion"];
      }
    ?>

    <div class="row">

      <div class="col-lg-7 col-xs-12">

        <div class="box box-info ca-resumen">
          <div class="box-header with-border">
            <h3 class="box-title">
              <?php echo htmlspecialchars($modelo["modelo"] . " — " . $modelo["nombre"]); ?>
            </h3>
            <span class="label label-default ca-marca"><?php echo htmlspecialchars($modelo["marca"]); ?></span>
            <span class="label label-primary ca-marca" title="Unidad FE"><?php echo htmlspecialchars($unidadLabel); ?></span>
          </div>
          <div class="box-body">
            <div class="ca-block">
              <div class="ca-label">Colores actuales</div>
              <div class="ca-chips" id="caChipsColores">
                <?php if (count($coloresExistentes) === 0) { ?>
                  <span class="ca-empty">Sin colores aún</span>
                <?php } else {
                  foreach ($coloresExistentes as $c) { ?>
                    <span class="ca-chip ca-chip-existente" title="<?php echo htmlspecialchars($c["cod_color"]); ?>">
                      <?php echo htmlspecialchars($c["cod_color"] . " · " . $c["color"]); ?>
                    </span>
                <?php }
                } ?>
              </div>
            </div>
            <div class="ca-block">
              <div class="ca-label">Tallas actuales</div>
              <div class="ca-chips" id="caChipsTallas">
                <?php if (count($tallasExistentes) === 0) { ?>
                  <span class="ca-empty">Sin tallas aún</span>
                <?php } else {
                  foreach ($tallasExistentes as $t) { ?>
                    <span class="ca-chip ca-chip-existente" title="<?php echo htmlspecialchars($t["cod_talla"]); ?>">
                      <?php echo htmlspecialchars($t["talla"]); ?>
                    </span>
                <?php }
                } ?>
              </div>
            </div>
          </div>
        </div>

        <div class="box box-success">
          <div class="box-header with-border">
            <h3 class="box-title">Qué vas a agregar</h3>
          </div>

          <form role="form" method="post" class="formularioArticulo" id="formularioAmpliarModelo">

            <div class="box-body">

              <input type="hidden" name="nuevaDescripcion" value="<?php echo htmlspecialchars($modelo["nombre"]); ?>">
              <input type="hidden" name="nuevoModelo" id="nuevoModelo" value="<?php echo htmlspecialchars($modelo["modelo"]); ?>">
              <input type="hidden" name="nuevaDescripcionMarca" value="<?php echo htmlspecialchars($modelo["marca"]); ?>">
              <input type="hidden" id="nuevaMarca" name="nuevaMarca" value="<?php echo htmlspecialchars($modelo["id_marca"]); ?>">
              <input type="hidden" name="nuevoVendedor" value="<?php echo htmlspecialchars($_SESSION["nombre"]); ?>">
              <input type="hidden" name="idVendedor" value="<?php echo htmlspecialchars($_SESSION["id"]); ?>">

              <input type="hidden" id="listaColores" name="listaColores" value="[]">
              <input type="hidden" id="listaVariantes" name="listaVariantes" value="[]">
              <input type="hidden" id="caColoresExistentes" value='<?php echo htmlspecialchars(json_encode($coloresExistentes, JSON_UNESCAPED_UNICODE), ENT_QUOTES, "UTF-8"); ?>'>
              <input type="hidden" id="caTallasExistentes" value='<?php echo htmlspecialchars(json_encode($tallasExistentes, JSON_UNESCAPED_UNICODE), ENT_QUOTES, "UTF-8"); ?>'>

              <div class="row ca-switches">
                <div class="form-group col-md-6">
                  <label>Descuentos por cantidad</label>
                  <label class="switch"><input type="checkbox" name="descuentos" value="1" <?php echo $chkDesc; ?>><span class="slider round"></span></label>
                </div>
                <div class="form-group col-md-6">
                  <label>Precios digitados</label>
                  <label class="switch"><input type="checkbox" name="precios" value="1" <?php echo $chkPrecios; ?>><span class="slider round"></span></label>
                </div>
                <div class="form-group col-md-6">
                  <label>Afecto a descuentos</label>
                  <label class="switch"><input type="checkbox" name="efectosDesc" value="1" <?php echo $chkEfectosDesc; ?>><span class="slider round"></span></label>
                </div>
                <div class="form-group col-md-6">
                  <label>Afecto a IGV</label>
                  <label class="switch"><input type="checkbox" name="efectosIGV" value="1" <?php echo $chkEfectosIGV; ?>><span class="slider round"></span></label>
                </div>
              </div>

              <div class="form-group">
                <label>Grupo de tallas</label>
                <div class="input-group">
                  <span class="input-group-addon"><i class="fa fa-expand"></i></span>
                  <select name="nuevoGrupoTalla" id="nuevoGrupoTalla" class="form-control" required>
                    <option value="">Seleccionar grupo</option>
                    <option value="TRUSA">TRUSA</option>
                    <option value="NIÑOS">NIÑOS</option>
                    <option value="BRASIER">BRASIER</option>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label>Tallas <small class="text-muted">(las que ya tiene el modelo quedan marcadas)</small></label>
                <div class="nuevaTalla ca-tallas" id="caTallasGrupo">
                  <span class="ca-empty">Elige un grupo de tallas</span>
                </div>
              </div>

              <div class="form-group">
                <label>Colores nuevos</label>
                <div class="nuevoColor ca-colores-nuevos" id="caColoresNuevos">
                  <span class="ca-empty ca-empty-colores">Agrega colores desde el catálogo →</span>
                </div>
              </div>

              <div class="ca-preview" id="caPreview">
                <i class="fa fa-info-circle"></i>
                <span id="caPreviewTexto">Selecciona colores nuevos y/o tallas nuevas.</span>
              </div>

              <button type="button" class="btn btn-default hidden-lg btnAgregarArticulo">Ver catálogo de colores</button>

            </div>

            <div class="box-footer clearfix">
              <a href="modelosjf" class="btn btn-default">Cancelar</a>
              <button type="submit" class="btn btn-primary pull-right" id="btnGuardarVariantes" disabled>
                Crear artículos
              </button>
            </div>

          </form>

          <?php
            $guardarArticulo = new ControladorArticulos();
            $guardarArticulo->ctrCrearArticuloModelo();
          ?>

        </div>

      </div>

      <div class="col-lg-5 col-md-12 col-sm-12 col-xs-12">
        <div class="box box-warning">
          <div class="box-header with-border">
            <h3 class="box-title">Catálogo de colores</h3>
          </div>
          <div class="box-body">
            <table class="table table-bordered table-striped dt-responsive tablaArticuloColores" width="100%">
              <thead>
                <tr>
                  <th class="text-center">Código</th>
                  <th class="text-center">Nombre</th>
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
window.document.title = "Agregar color / talla";
</script>
