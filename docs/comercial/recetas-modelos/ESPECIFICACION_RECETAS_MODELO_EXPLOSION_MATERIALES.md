# Recetas por modelo para explosión de materiales

## Propósito

Reemplazar el uso operativo de las tarjetas individuales por artículo (`tarjetasjf`) por recetas parametrizadas por **modelo**. Una receta debe resolver, para cada artículo activo del modelo (color y talla), la materia prima y el consumo necesarios para la explosión de materiales.

La prioridad es eliminar la carga artículo por artículo y conservar precisión en variantes de color, talla y color+talla.

## Contexto actual

El módulo actual crea una tarjeta para cada `articulojf.articulo` y sus detalles también se guardan por artículo. Esto obliga a repetir la misma receta para sus combinaciones de color y talla, complica cambios y no representa una receta base con excepciones.

No eliminar `tarjetasjf` ni `detalles_tarjetajf` en esta implementación: tienen dependencias en producción, corte, reportes y costos. El nuevo módulo debe coexistir inicialmente y convertirse en la fuente para una nueva explosión de materiales.

## Conceptos de negocio

| Concepto | Definición |
|---|---|
| Modelo | Agrupación de artículos que comparten diseño, identificada por `articulojf.modelo`. |
| Artículo / variante | Una combinación concreta de modelo, color y talla (`articulojf`). |
| Receta | Conjunto versionado de insumos para un modelo. |
| Línea de receta | Un insumo lógico: por ejemplo tela principal, etiqueta de talla o elástico. |
| Sublínea | Clasificación de MP usada como guía y validación para una línea; no es la MP que usa finalmente la explosión. |
| Resolución | MP y consumo final que corresponde a una variante de artículo. |

### Regla crítica: tela principal

- Una receta tiene exactamente **una** línea marcada como `es_tela_principal = 1`.
- La tela principal es obligatoria para publicar una receta.
- Cada artículo activo cubierto por la receta debe resolver una única MP de tela principal.
- La explosión dirigida a corte toma la tela principal como material que direcciona el corte. Los otros insumos se consideran complementarios.
- No se permiten dos líneas principales ni una variante con más de una resolución principal aplicable.

## Variantes soportadas

Cada línea define una regla de resolución:

| Regla | Uso | Clave de variante |
|---|---|---|
| `GENERAL` | Misma MP y consumo para todas las variantes | Ninguna |
| `COLOR` | Tela u otro insumo cambia por color | `cod_color` |
| `TALLA` | Etiquetas u otros insumos cambian por talla | `cod_talla` |
| `COLOR_TALLA` | Insumo o consumo cambia por combinación | `cod_color` + `cod_talla` |

La resolución final de una línea sigue este orden, de mayor a menor especificidad:

1. Coincidencia `COLOR_TALLA`.
2. Coincidencia por `COLOR` o por `TALLA`, según la regla configurada.
3. Resolución `GENERAL` de la línea.
4. Si no hay resolución, marcar el artículo como incompleto; no permitir publicar si afecta la tela principal.

No hacer una búsqueda automática por sublínea para elegir una MP: la persona debe asignar explícitamente la MP final. La sublínea solo valida que la MP seleccionada sea compatible, si ese dato está disponible.

## Modelo de datos propuesto

Usar nombres nuevos para no alterar las tablas antiguas. Ajustar los tipos y claves foráneas a las convenciones reales de la base antes de ejecutar la migración.

### `recetas_modelo`

| Campo | Descripción |
|---|---|
| `id` | PK. |
| `modelo` | Código o identificador del modelo. |
| `version` | Entero correlativo por modelo. |
| `estado` | `BORRADOR`, `PUBLICADA`, `ARCHIVADA`. Solo una publicada por modelo. |
| `vigente_desde` | Fecha de inicio opcional. |
| `vigente_hasta` | Fecha de fin opcional. |
| `id_usuario_crea`, `id_usuario_actualiza` | Auditoría. |
| `created_at`, `updated_at` | Auditoría. |

Índice único: `(modelo, version)`.

### `recetas_modelo_detalles`

| Campo | Descripción |
|---|---|
| `id` | PK. |
| `id_receta_modelo` | FK a receta. |
| `orden` | Orden de visualización/explosión. |
| `nombre_rol` | Ej.: Tela principal, Etiqueta de talla, Elástico. |
| `es_tela_principal` | Booleano. Solo uno por receta. |
| `codigo_sublinea` | Sublínea de MP esperada. |
| `regla_variante` | `GENERAL`, `COLOR`, `TALLA`, `COLOR_TALLA`. |
| `unidad` | Unidad del consumo. |
| `consumo_base` | Consumo general de respaldo; decimal, no entero. |
| `mp_base_codigo` | MP general de respaldo; nullable si la regla requiere variantes completas. |
| `activo` | Permite retirar una línea sin borrar historial. |

Validaciones de aplicación: un único `es_tela_principal = 1` por receta y valores permitidos para `regla_variante`.

### `recetas_modelo_variantes`

Una fila es una excepción o una asignación específica de una línea.

| Campo | Descripción |
|---|---|
| `id` | PK. |
| `id_receta_modelo_detalle` | FK a línea. |
| `cod_color` | Nullable; obligatorio según regla. |
| `cod_talla` | Nullable; obligatorio según regla. |
| `mp_codigo` | Código de MP final, obligatorio. |
| `consumo` | Consumo final; nullable solo si se usa el consumo base. |
| `observacion` | Justificación opcional. |

Índice único recomendado: `(id_receta_modelo_detalle, cod_color, cod_talla)`. Para MySQL, considerar normalizar nulos con columnas auxiliares o validar duplicados en aplicación, ya que los índices únicos permiten múltiples `NULL`.

## Pantallas y UX

### 1. Administrar recetas

Reemplaza la tabla masiva de tarjetas por una tabla de recetas por modelo:

| Modelo | Versión | Estado | Variantes cubiertas | Tela principal | Alertas | Acción |
|---|---:|---|---:|---|---:|---|

Acciones: crear receta, editar borrador, duplicar versión, previsualizar explosión, publicar, archivar. No ofrecer eliminar una receta publicada.

Filtros: modelo, estado, alerta de cobertura y vigencia.

### 2. Editor de receta (un solo flujo)

**Cabecera:** modelo, versión, estado y total de artículos activos encontrados (colores/tallas).

**Paso A — receta base:** una tabla editable con rol, sublínea, regla de variante, MP base, consumo base y acción. La primera línea sugerida es `Tela principal`; debe quedar marcada visualmente como obligatoria.

**Paso B — variantes de la línea seleccionada:** mostrar únicamente las combinaciones que correspondan a la regla.

- `GENERAL`: no abrir matriz; usar la MP/consumo base.
- `COLOR`: filas por color del modelo.
- `TALLA`: filas por talla del modelo.
- `COLOR_TALLA`: matriz color × talla.

Cada fila permite buscar una MP, muestra código, descripción, color y sublínea, y permite sobrescribir el consumo. Usar colores de estado: completo, incompleto y MP incompatible con la sublínea.

**Paso C — revisión:** tabla no editable por artículo:

| Artículo | Color | Talla | Tela principal resuelta | Insumos completos | Estado |
|---|---|---|---|---|---|

Botón `Publicar receta` deshabilitado mientras haya una tela principal no resuelta. Si los insumos complementarios incompletos también deben bloquear la publicación, hacerlo configurable pero inicialmente bloquearlos para no generar explosiones parciales.

## Algoritmo de explosión

Entrada: modelo, artículo (o color+talla), cantidad a producir y receta publicada vigente.

1. Obtener el artículo de `articulojf` y su `modelo`, `cod_color` y `cod_talla`.
2. Obtener la receta publicada vigente del modelo.
3. Por cada línea activa, resolver la MP y consumo según la regla y el orden de precedencia definido arriba.
4. Validar que existe una única tela principal resuelta.
5. Calcular `consumo_total = consumo_resuelto × cantidad`.
6. Agrupar resultados por MP si la salida requiere totales consolidados, conservando el rol y la marca de tela principal.
7. Si hay cualquier línea obligatoria sin resolver, devolver error legible con artículo, rol y variante faltante. Nunca sustituir silenciosamente una MP.

La salida debe distinguir `es_tela_principal`, para que corte use esa fila como material direccionador.

## Reglas de publicación y versionado

- Editar una receta publicada debe crear una nueva versión en `BORRADOR`; no modificar el historial.
- Al publicar una versión, cerrar o archivar la publicación previa del modelo según las fechas de vigencia.
- Órdenes/explosiones ya generadas deben guardar el `id_receta_modelo` y la `version` usada para trazabilidad.
- No derivar la explosión desde `tarjetasjf` nueva ni actualizar tarjetas individuales automáticamente en la primera fase.

## Plan de implementación para Cursor

1. Localizar cómo se identifican modelo, color, talla, materia prima, sublínea y unidad en la base actual. Confirmar los nombres reales antes de crear el SQL.
2. Crear un archivo SQL de migración en `docs/sql/` con las tres tablas, índices y restricciones posibles para el motor actual. No tocar ni eliminar tablas antiguas.
3. Implementar un modelo/controlador nuevo para recetas; no reutilizar `ModeloTarjetas` para escritura nueva.
4. Implementar endpoints AJAX específicos: listar recetas, cargar artículos del modelo, guardar borrador, guardar líneas, guardar variantes, validar cobertura, previsualizar explosión y publicar.
5. Crear vistas nuevas dentro de `vistas/modulos/tarjetas/` o un módulo `recetas-modelo`; mantener las vistas antiguas disponibles durante la transición.
6. Implementar la resolución de receta como una función/servicio único y reutilizarla desde la futura explosión. No duplicar la lógica en JavaScript, controlador y reportes.
7. Integrar gradualmente la explosión de materiales para leer la receta publicada. Mantener un fallback explícito al mecanismo viejo solo si el modelo no tiene receta publicada, con alerta visible.
8. Agregar pruebas manuales y, si el proyecto ya tiene infraestructura, automatizadas para las combinaciones descritas abajo.

## Casos de aceptación mínimos

1. Un modelo con tres colores y cuatro tallas puede configurarse sin crear doce tarjetas individuales.
2. La tela principal puede variar por color y cada combinación color+talla recibe exactamente una MP principal.
3. La etiqueta puede variar por talla, independiente del color.
4. Un elástico puede variar por color+talla.
5. Una combinación sin tela principal no permite publicar ni explotar materiales.
6. Una línea con MP de sublínea incompatible muestra error antes de publicar.
7. Al cambiar una receta publicada se conserva la versión anterior y las explosiones históricas mantienen su versión.
8. La explosión consolida consumo por MP, pero conserva cuál fue la tela principal.
9. Los módulos actuales que leen `tarjetasjf` siguen funcionando sin regresión durante esta fase.

## No incluido en la primera fase

- Migración automática de todas las tarjetas antiguas a recetas por modelo: requiere revisión de datos y debe ser una fase aparte.
- Eliminación de tarjetas viejas.
- Elección automática de MP por nombre, color o sublínea.
- Cambios en costos, salvo que el nuevo motor de explosión se integre después de validar resultados.
