<div class="modal fade" id="modalPreviewExplosionReceta" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg rm-modal-explosion" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">
					Previsualizar explosión
					<small id="previewExplosionSubtitulo"></small>
				</h4>
			</div>
			<div class="modal-body">
				<input type="hidden" id="previewIdReceta">
				<input type="hidden" id="previewModeloReceta">
				<p class="text-muted" style="margin-top:0;">
					Ingresá la cantidad de prendas por <strong>color</strong> y <strong>talla</strong>.
					El sistema calcula la materia prima de todas las combinaciones juntas.
					Si cargás un número arriba, un clic en el color o en la talla lo aplica a esa fila o columna.
				</p>
				<div class="rm-preview-atajos">
					<label>Aplicar a todas las celdas</label>
					<input type="number" class="form-control input-sm" id="previewCantidadTodas" min="0" step="1" placeholder="Ej. 10">
					<button type="button" class="btn btn-default btn-sm" id="btnPreviewAplicarTodas">Aplicar</button>
					<button type="button" class="btn btn-default btn-sm" id="btnPreviewLimpiarMatriz">Limpiar</button>
					<span class="rm-preview-total-live">Total: <strong id="previewTotalPrendas">0</strong> prendas</span>
				</div>
				<div class="rm-preview-matriz-wrap" id="previewMatrizWrap">
					<div class="text-muted" id="previewMatrizVacio">Cargando colores y tallas…</div>
					<table class="table table-bordered table-condensed rm-preview-matriz" id="previewMatrizCantidades" style="display:none;">
						<thead></thead>
						<tbody></tbody>
						<tfoot></tfoot>
					</table>
				</div>
				<div class="rm-preview-acciones">
					<button type="button" class="btn btn-info" id="btnEjecutarPreviewReceta">
						<i class="fa fa-eye"></i> Calcular explosión
					</button>
					<button type="button" class="btn btn-success" id="btnDescargarExplosionExcel" disabled>
						<i class="fa fa-file-excel-o"></i> Descargar Excel
					</button>
				</div>
				<div id="previewExplosionResultado"></div>
			</div>
		</div>
	</div>
</div>
