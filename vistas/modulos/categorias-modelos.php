<?php
if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "categorias_modelos")) {
    denegarAccesoModulo();
    return;
}
$puedeEditarCategoriasModelos = function_exists("usuarioPuedeModulo")
    && usuarioPuedeModulo("gestion_comercial", "categorias_modelos", "editar");
$modeloInicialCat = isset($_GET["modelo"]) ? trim((string) $_GET["modelo"]) : "";
?>
<div class="content-wrapper cat-modelos-page">

    <section class="content-header">
        <h1>Clasificación de modelos <small>emparejar disponibles → subcategoría</small></h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Clasificación de modelos</li>
        </ol>
    </section>

    <section class="content">
        <div class="row cat-match-top">
            <div class="col-md-4 col-sm-6">
                <div class="info-box bg-aqua" style="margin-bottom:10px;min-height:70px;">
                    <span class="info-box-icon" style="height:70px;width:70px;line-height:70px;font-size:28px;"><i class="fa fa-hourglass-half"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Pendientes</span>
                        <span class="info-box-number" id="catMatchPendientes">—</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="info-box bg-green" style="margin-bottom:10px;min-height:70px;">
                    <span class="info-box-icon" style="height:70px;width:70px;line-height:70px;font-size:28px;"><i class="fa fa-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Clasificados</span>
                        <span class="info-box-number" id="catMatchClasificados">—</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-12">
                <div class="info-box bg-yellow" style="margin-bottom:10px;min-height:70px;">
                    <span class="info-box-icon" style="height:70px;width:70px;line-height:70px;font-size:28px;"><i class="fa fa-sitemap"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Destino actual</span>
                        <span class="info-box-number" id="catMatchDestinoLabel" style="font-size:16px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Ninguno</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row cat-match-layout">
            <div class="col-md-4">
                <div class="box box-primary cat-match-panel">
                    <div class="box-header with-border">
                        <h3 class="box-title">Categorías y subcategorías</h3>
                        <div class="box-tools pull-right">
                            <a href="index.php?ruta=categorias-subcategorias-modelos" class="btn btn-box-tool" title="Mantener catálogo"><i class="fa fa-cog"></i></a>
                        </div>
                    </div>
                    <div class="box-body" style="padding:0;">
                        <div id="catMatchArbol" class="cat-match-arbol">
                            <p class="text-muted text-center" style="padding:20px;">Cargando…</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="box box-success cat-match-panel">
                    <div class="box-header with-border">
                        <h3 class="box-title">Disponibles <small id="catMatchDispTitulo">(elige una subcategoría)</small></h3>
                    </div>
                    <div class="box-body" style="padding-top:8px;">
                        <div class="form-group" style="margin-bottom:8px;flex:0 0 auto;">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="catMatchBuscar" placeholder="Buscar modelo…">
                                <span class="input-group-btn">
                                    <select class="form-control" id="catMatchMarca" style="width:auto;display:inline-block;min-width:110px;">
                                        <option value="">Marca</option>
                                    </select>
                                </span>
                            </div>
                        </div>
                        <?php if ($puedeEditarCategoriasModelos) { ?>
                        <div class="cat-match-toolbar">
                            <label class="checkbox-inline" style="margin:0 8px 0 0;">
                                <input type="checkbox" id="catMatchCheckAllDisp"> Visibles
                            </label>
                            <button type="button" class="btn btn-xs btn-default" id="catMatchSelSugeridos">Sugeridos</button>
                            <button type="button" class="btn btn-xs btn-default" id="catMatchLimpiar">Limpiar</button>
                            <span class="text-muted" id="catMatchSelInfo">0 sel.</span>
                            <button type="button" class="btn btn-sm btn-success btn-block" id="catMatchAgregar" disabled style="margin-top:6px;">
                                <i class="fa fa-arrow-right"></i> Agregar al destino
                            </button>
                        </div>
                        <?php } ?>
                        <div class="cat-match-lista" id="catMatchListaDisp">
                            <p class="text-muted text-center" style="padding:24px;">Selecciona una subcategoría a la izquierda</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="box box-info cat-match-panel">
                    <div class="box-header with-border">
                        <h3 class="box-title">En esta subcategoría</h3>
                        <span class="badge bg-aqua pull-right" id="catMatchEnDestinoCount">0</span>
                    </div>
                    <div class="box-body" style="padding-top:8px;">
                        <?php if ($puedeEditarCategoriasModelos) { ?>
                        <div class="cat-match-toolbar">
                            <label class="checkbox-inline" style="margin:0 8px 0 0;">
                                <input type="checkbox" id="catMatchCheckAllDest"> Todos
                            </label>
                            <button type="button" class="btn btn-xs btn-danger" id="catMatchQuitar" disabled>
                                <i class="fa fa-times"></i> Quitar
                            </button>
                            <button type="button" class="btn btn-xs btn-warning" id="catMatchMover" disabled title="Mueve los seleccionados a la subcategoría activa de la izquierda">
                                <i class="fa fa-exchange"></i> Mover aquí
                            </button>
                            <span class="text-muted" id="catMatchSelDestInfo">0</span>
                        </div>
                        <?php } ?>
                        <div class="cat-match-lista" id="catMatchListaDestino">
                            <p class="text-muted text-center">—</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
window.CAT_MODELOS_PUEDE_EDITAR = <?php echo $puedeEditarCategoriasModelos ? "true" : "false"; ?>;
window.CAT_MODELOS_MODELO_INICIAL = <?php echo json_encode($modeloInicialCat); ?>;
</script>
