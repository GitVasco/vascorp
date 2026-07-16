# Plan: Ficha gerencial de modelos

**Estado:** arquitectura y hoja de ruta propuesta.  
**Referencia visual:** [`docs/ficha_modelos.png`](ficha_modelos.png).  
**Objetivo:** ofrecer en una sola vista la información gerencial de un modelo —ventas, stock, colores/tallas, producción, rentabilidad y alertas— para evitar consolidar múltiples archivos Excel.

## 1. Principio de implementación

La ficha no debe ser una pantalla gigante con consultas copiadas desde varios módulos. Debe tener una sola capa de datos por modelo y período que alimente tarjetas, matrices, rankings y alertas. Cada métrica debe mostrar su fecha de actualización, fórmula y fuente.

La vista será de solo lectura para gerencia en su primera versión. La edición de artículos, precios, stock, producción y costos seguirá ocurriendo en sus módulos actuales. La ficha tendrá enlaces profundos hacia esos módulos cuando haga falta corregir información.

## 2. Qué ya existe en el proyecto

| Dominio | Datos/implementación existente | Aprovechamiento en la ficha |
|---|---|---|
| Identidad del modelo | `modelojf`: código, nombre, estado, tipo, línea, imagen, marca y operaciones. Existe el maestro `modelosjf`. | Cabecera, imagen, marca, línea, estado y enlace al maestro. |
| Variantes | `articulojf`: artículo/SKU, modelo, marca, color (`cod_color`, `color`), talla, estado. | Matriz color–talla, colores/tallas disponibles y detalle de SKU. |
| Inventario y demanda operativa | `articulojf`: `stock`, `pedidos`, `taller`, `servicio`, `alm_corte`, `ord_corte`, `proyeccion`, `prod`, `ult_mes`, urgencia. Ya se usa en urgencias de artículos. | Stock disponible, stock comprometido, producción en proceso y alertas de falta. |
| Ventas por artículo/modelo | `movimientosjf_AAAA` unido a `articulojf`, y `ventajf` para cabecera. `ModeloMetricasComerciales` ya usa estos movimientos para ventas permitidas por modelo. | Ventas monetarias, unidades, color/talla, vendedor, cliente, zona y evolución. |
| Producción | Movimientos tipo `E20`, consultas `mdlMovProdMod()` y módulos de producción. | Unidades producidas, evolución y contraste producción vs venta. |
| Precios | `preciojf` y métodos `mdlVerPrecios()` / módulo de modelos. | Precio de lista y, después, precio promedio vendido. |
| Costos | Existe `costos-modelo.php`, pero usa JSON históricos/archivos (`vistas/json`) y datos de 2022. | Referencia de UX solamente; **no usar como fuente oficial** de margen/utilidad hasta normalizar costos. |
| Cobertura comercial | Grupos de marcas y cobertura vendedor–marca ya planificados/implementados en modelos de métricas. | Filtros y análisis de ventas por vendedor, sin duplicar regla de cobertura. |

### Hallazgos importantes

- La mayor parte del MVP puede construirse con datos ya disponibles en base de datos.
- No hay una fuente única confirmada de costo unitario vigente por modelo/artículo y período. Por eso **utilidad y margen reales no deben aparecer en la Fase 1** con datos estimados o JSON antiguos.
- Las tablas de movimientos están particionadas por año (`movimientosjf_YYYY`). La capa de datos debe resolver esta partición de forma segura y no dejar años fijos como ocurre en consultas antiguas.
- Los indicadores deben usar ventas netas válidas y tratar devoluciones/notas de crédito igual que el motor comercial; no usar solamente sumas de cabecera si se requiere color/talla.

## 3. Arquitectura propuesta

```text
Ruta / ficha
index.php?ruta=ficha-modelo&modelo={codigo}&anio={YYYY}&mes={MM}
        │
        ├── Vista PHP: filtros, contenedores y permisos
        ├── JS: carga progresiva de zonas vía AJAX
        ├── ControladorFichaGerencialModelos
        ├── ModeloFichaGerencialModelos
        │     ├── catálogo y variantes
        │     ├── ventas por modelo/variante
        │     ├── inventario y producción
        │     ├── clientes, vendedores y zonas
        │     ├── costos (fase posterior)
        │     └── reglas de salud/alertas
        └── JSON por zona: resumen, matriz, rankings, evolución, alertas
```

Archivos propuestos:

- `controladores/ficha-gerencial-modelos.controlador.php`
- `modelos/ficha-gerencial-modelos.modelo.php`
- `ajax/ficha-gerencial-modelos.ajax.php`
- `vistas/modulos/ficha-gerencial-modelos.php`
- `vistas/js/ficha-gerencial-modelos.js`
- `docs/sql/indices-ficha-gerencial-modelos.sql`

No reutilizar `vistas/modulos/costos/costos-modelo.php` como backend: mezcla cálculo, HTML y archivos JSON de un año histórico. La nueva capa debe consultar BD con parámetros preparados y devolver JSON.

## 4. Contrato de métricas

Antes de programar cada zona, Cursor debe centralizar estas definiciones en un único servicio/modelo y mostrarlas como ayuda en la pantalla.

| Métrica | Definición inicial propuesta | Fuente |
|---|---|---|
| Ventas S/ | `SUM(m.total)` de líneas de venta válidas del modelo en el período, incluyendo devoluciones como importe/cantidad negativa según regla actual. | `movimientosjf_AAAA` + `articulojf` |
| Unidades vendidas | `SUM(m.cantidad)` de las mismas líneas. | `movimientosjf_AAAA` |
| Stock físico | `SUM(MAX(stock, 0))` de los artículos activos del modelo. | `articulojf` |
| Stock disponible | Stock físico menos pedidos comprometidos; mostrar componentes por separado. | `articulojf.stock - articulojf.pedidos` |
| En proceso | Taller + servicio + almacén de corte + orden de corte; nunca sumarlo al stock disponible sin etiqueta. | `articulojf` |
| Rotación | Unidades vendidas del período ÷ inventario promedio. Si no hay inventario histórico confiable, mostrar “pendiente de histórico” y no inventar el promedio. | ventas + inventario histórico futuro |
| Cobertura/días inventario | Stock disponible ÷ promedio diario de ventas de los últimos N días configurables. | `articulojf` + movimientos |
| Precio promedio | Venta neta ÷ unidades netas, evitando división por cero. | movimientos |
| Margen/utilidad | Venta neta menos costo de venta histórico; no habilitar hasta contar con costo unitario/fecha confiable. | nueva fuente de costos |
| Ranking | Posición por ventas netas del período dentro del universo filtrado (marca/línea/categoría). | movimientos + catálogo |

## 5. Zonas de la vista y fases

### Fase 0 — Fundaciones y validación de datos

**Propósito:** definir métricas, asegurar fuentes y evitar que la ficha muestre números contradictorios.

- [ ] Crear permisos `gestion_comercial.ficha_modelos.ver`; edición no aplica en la ficha inicial.
- [ ] Registrar ruta, menú “Análisis de modelos” y acceso desde `modelosjf` mediante botón “Ver ficha”.
- [ ] Crear búsqueda de modelo por código/nombre, filtros de período, marca y comparación.
- [ ] Implementar `mdlResolverTablaMovimientos($anio)` con verificación de tabla existente; nunca concatenar un año no validado.
- [ ] Definir tipos de documento, anulaciones, devoluciones y notas de crédito para ventas por línea, reutilizando la regla del motor comercial.
- [ ] Crear consultas de conciliación: total de la ficha vs reporte de ventas existente, para una muestra de modelos y períodos.
- [ ] Auditar nulos/duplicados de modelo, marca, color, talla y artículos sin modelo.
- [ ] Revisar `EXPLAIN` e instalar solo índices necesarios sobre fecha/tipo/artículo/vendedor en las tablas de movimientos.

**Entregable:** ruta funcional con selector de modelo y un panel técnico de conciliación solo para administrador.

### Fase 1 — Ficha operativa MVP

**Propósito:** entregar una ficha útil de una sola pantalla con información fiable ya disponible.

Zonas de la imagen que entran:

1. **Cabecera:** imagen, código, nombre, marca, línea, tipo, estado y período.
2. **Tarjetas:** ventas netas, unidades vendidas, stock físico, stock disponible y pedidos comprometidos.
3. **Variantes:** colores y tallas disponibles; matriz color–talla con stock disponible y unidades vendidas del período.
4. **Rankings básicos:** colores, tallas, top 10 combinaciones color–talla.
5. **Evolución:** gráfico mensual de ventas y unidades del año seleccionado.
6. **Detalle:** tabla paginada de artículos/SKU con color, talla, stock, pedidos, en proceso, ventas y última venta.

**Criterio de aceptación:** gerencia puede elegir un modelo y período, entender qué se vende y qué queda disponible por variante sin abrir un Excel.

### Fase 2 — Comercial: dónde y quién vende

**Propósito:** explicar el desempeño comercial del modelo.

Zonas de la imagen que entran:

- Top vendedores y su participación.
- Top clientes, número de pedidos y concentración de ventas.
- Ventas por zona/departamento (tabla primero; mapa solo cuando la fuente de zona esté validada).
- Comparativo contra período/año anterior: ventas, unidades y precio promedio.
- Preguntas rápidas basadas en reglas determinísticas: color más vendido, talla líder, vendedor/cliente líder, dónde se vende más.

**Reglas:** respetar grupos de marcas y excluir ventas fuera de cobertura cuando el análisis sea por vendedor; el análisis global del modelo debe poder visualizar toda la venta con su filtro explícito.

### Fase 3 — Salud de inventario y alertas

**Propósito:** convertir stock y ventas en decisiones de abastecimiento visibles.

Zonas de la imagen que entran:

- Días de inventario y cobertura por modelo, color y talla.
- Rotación, inicialmente con la definición disponible y etiqueta de nivel de confianza.
- Semáforo por combinación color–talla: excelente/bueno/regular/bajo/crítico, con umbrales configurables.
- Alertas: quiebre próximo, exceso de stock, talla/color de baja rotación, variante líder sin cobertura suficiente.
- Lista “¿Qué debo hacer?” producida por reglas auditables, con enlace a la variante o módulo operativo correspondiente.

**No incluir aún:** recomendaciones con IA no explicables ni producción automática.

### Fase 4 — Rentabilidad y costos confiables

**Propósito:** habilitar utilidad, margen y decisiones de precio con trazabilidad.

Requiere implementar el módulo independiente descrito en `PLAN_COSTOS_MENSUALES_MODELO.md`, que será la fuente de verdad de costo mensual por modelo. La ficha solo consume costos **aprobados** de ese módulo; no permite modificarlos.

Modelo de datos de referencia:

```text
costo_modelo_periodojf
  modelo / artículo opcional
  fecha_inicio, fecha_fin
  costo_material, mano_obra, cif, costo_total
  fuente, estado_aprobacion, usuario, fechas de auditoría
```

Una vez aprobada:

- [ ] Margen promedio y utilidad del período por modelo/variante.
- [ ] Ranking por rentabilidad y no solo por ventas.
- [ ] Alertas de variantes que venden pero destruyen margen.
- [ ] Comparación de precio promedio vendido vs costo y lista de precios.

Los costos históricos se deben congelar por vigencia; no recalcular utilidades antiguas con el costo de hoy.

### Fase 5 — Pronóstico y planificación de producción

**Propósito:** estimar demanda y sugerir producción, siempre con revisión humana.

- [ ] Promedio móvil de 3/6/12 meses y estacionalidad por modelo/color/talla.
- [ ] Proyección de próximo mes y confianza del pronóstico.
- [ ] Stock objetivo y producción sugerida: demanda prevista + stock de seguridad − disponible − en proceso utilizable.
- [ ] Parámetros configurables: horizonte, stock de seguridad, lead time, múltiplo de producción y capacidad.
- [ ] Simulación; no generar orden de corte/producción automáticamente en la primera entrega.

## 6. Diseño de datos y rendimiento

- Cargar por AJAX cada zona después de la cabecera; no bloquear la vista esperando rankings, mapa y gráficos.
- Usar agregados por lote para todas las variantes de un modelo. No lanzar una consulta por color o talla.
- Para comparativos entre años, consultar solo los años necesarios; validar la existencia de cada `movimientosjf_YYYY`.
- Si la vista se vuelve lenta, crear una tabla de resumen diaria/mensual por modelo–color–talla, actualizada por proceso programado. No crearla antes de medir la Fase 1.
- Toda respuesta AJAX debe validar permiso, modelo, año, mes y rango máximo permitido.
- Los textos de alerta deben guardar la métrica y umbral que los originan; no presentar conclusiones sin evidencia.

## 7. Lista de implementación para Cursor

### Estructura inicial

- [ ] Crear los cinco archivos de la arquitectura propuesta.
- [ ] Añadir permisos, ruta, menú y botón de acceso desde el maestro de modelos.
- [ ] Implementar endpoint `resumen` (cabecera + tarjetas) y manejo de estados vacío/error.
- [ ] Implementar filtros de modelo/período; persistirlos en URL para compartir la ficha.
- [ ] Registrar fecha/hora de actualización y fuente de cada bloque.

### Métricas del MVP

- [ ] Catálogo/imagen desde `modelojf` y marca desde `marcasjf`.
- [ ] Variantes e inventario desde `articulojf`.
- [ ] Ventas/unidades por línea desde `movimientosjf_AAAA` + `articulojf`.
- [ ] Matriz y rankings color–talla agregados en SQL.
- [ ] Serie mensual para evolución.
- [ ] Pruebas de conciliación contra informes existentes y casos con devolución/anulación.

### Entregas posteriores

- [ ] Fase 2: vendedores, clientes, zonas y comparativo anual.
- [ ] Fase 3: cobertura, semáforos, alertas y acciones.
- [ ] Fase 4: modelo de costo con vigencia y rentabilidad.
- [ ] Fase 5: pronóstico y sugerencia de producción.

## 8. Riesgos que Cursor debe tratar explícitamente

| Riesgo | Tratamiento |
|---|---|
| Años de movimientos particionados | Resolver tabla mediante lista blanca y validar existencia. |
| Totales diferentes entre reportes | Publicar fórmula/fuente y ejecutar conciliación antes de mostrar KPI oficial. |
| Devoluciones/anulaciones | Compartir las reglas de ventas del motor comercial y probar documentos reales. |
| Stock inconsistente | Mostrar físico, comprometido y en proceso separados; no ocultar valores negativos. |
| Costo de 2022/JSON | No usarlo para margen gerencial actual. Crear Fase 4 con vigencia y aprobación. |
| Cálculo lento | Agregación SQL por lote, `EXPLAIN`, índices medidos y resumen materializado solo si es necesario. |
| Recomendaciones discutibles | Primero reglas con umbrales visibles; IA solo como fase posterior explicable. |

## 9. Alcance confirmado de la Fase 1

1. La ficha muestra solo modelos activos, con filtro por marca.
2. Stock disponible = `stock - pedidos`; taller, servicio, almacén de corte y orden de corte se muestran como cantidades separadas en proceso.
3. Las ventas incluyen todos los canales y vendedores por defecto. En una fase posterior se puede agregar el filtro opcional “solo cobertura comercial”.
4. Margen y utilidad quedan fuera del MVP. Se implementará un módulo independiente de carga y aprobación de costos mensuales por modelo.
5. El comparativo estándar será contra el mismo período del año anterior.
6. Modelos de conciliación inicial: `10026`, `10273`, `10353`, `10054`.

## 10. Orden de inicio para Cursor

1. Implementar Fase 0 y conciliar primero los modelos `10026`, `10273`, `10353` y `10054`.
2. Implementar la Fase 1 completa sin margen, utilidad, pronóstico ni IA.
3. Implementar el módulo de costos mensuales por separado, según `PLAN_COSTOS_MENSUALES_MODELO.md`.
4. Solo cuando existan costos aprobados, iniciar la Fase 4 de rentabilidad en esta ficha.
