# Plan: Proyección comercial de ventas por modelo

**Estado:** Fase 0 + Fase 1 implementadas en código (UX definitiva diferida).  
**Propietario funcional:** un usuario responsable de la proyección comercial.  
**Consumidores:** Comercial, Producción, Logística, Gerencia y vendedores (consulta/control).  
**Unidad de planeamiento:** modelo, global para toda la empresa, por mes.  
**Horizonte:** uno o varios meses consecutivos; por ejemplo, julio–diciembre.  
**Resultado oficial:** una única proyección oficial vigente por modelo y mes, en unidades y soles valorizados a lista 9. Se puede corregir después de publicar; el historial queda en auditoría.

## 1. Objetivo

Crear un módulo que permita preparar, revisar y publicar la demanda comercial esperada por cada modelo. La proyección será la fuente de planificación para Producción y Logística; para Ventas será una meta/control visible, no una captura distribuida por vendedor.

El módulo debe explicar cada cifra: mostrar el historial y contexto que la sustentan, los factores aplicados, el usuario que la registró y los cambios posteriores. No debe convertirse en una caja negra ni reemplazar la decisión humana.

## 2. Decisiones de negocio confirmadas

| Tema | Decisión |
|---|---|
| Granularidad principal | Modelo global de empresa; no se divide inicialmente por vendedor, zona, cliente ni marca. |
| Tiempo | Un registro por modelo y mes. La pantalla permite trabajar un rango de meses, por ejemplo `2026-07` a `2026-12`. |
| Unicidad oficial | Existe una sola proyección oficial por `(anio, mes, modelo)`. Un borrador no puede incluir ni pisar un mes–modelo ya publicado; el sistema lo excluye o avisa. |
| Correcciones | Tras publicar se edita la misma fila (no se crean versiones paralelas). Cada cambio exige motivo y se registra en auditoría (antes/después, usuario, fecha). |
| Responsable | Un único usuario/cargo comercial prepara y publica la proyección. |
| Escenarios/versiones | No hay escenarios conservador/base/optimista. El histórico de cambios es la auditoría, no filas versionadas. |
| Medidas | Unidades proyectadas y valor en soles. |
| Valor monetario oficial | `unidades_oficiales × precio_lista_9 vigente al publicar`. Cada modelo tiene un único precio en lista 9; ese valor se congela como snapshot al publicar la línea. |
| Uso aguas abajo | Producción y Logística consultan solo líneas `PUBLICADO`/`CERRADO`; vendedores la ven como referencia/control contra su venta real y sus retos/metas. |
| Factores externos | Campañas, cambios de precio, lanzamientos, eventos, publicidad/redes sociales y otros factores documentados. |
| Importación masiva | Fuera de alcance en Fase 1; se define más adelante. |

## 3. Qué ya existe y debe reutilizarse

| Necesidad del módulo | Fuente actual | Uso requerido |
|---|---|---|
| Modelo, nombre, estado, marca y categorías | `modelojf`, `marcasjf`, categorías de modelos | Catálogo de modelos proyectables y filtros. Solo modelos activos por defecto. |
| SKU, colores/tallas e inventario | `articulojf` | Consolidar por modelo: `stock`, `pedidos`, `taller`, `servicio`, `alm_corte`, `ord_corte`. |
| Ventas históricas | `movimientosjf_AAAA` + `articulojf` + cabecera `ventajf` | Unidades y venta neta por modelo y mes. Las tablas están particionadas por año. |
| Regla de venta válida | `ModeloFichaGerencialModelos` / motor comercial | Incluir `S02`, `S03`, `S70`, `S05`, `E05`; excluir cabeceras anuladas y conservar el tratamiento vigente de devoluciones. |
| Precio lista 9 | `preciojf` y consultas usadas por ficha de modelos | Obtener el precio de lista 9 y congelar el aplicado en cada publicación. |
| Contexto comercial | `ficha-gerencial-modelos` | Reutilizar ventas mensuales, comparación interanual, colores/tallas, zonas, vendedores y clientes líderes. |
| Costos/margen | `costos_modelo_mensualjf` | Mostrar costo/margen aprobado como información de decisión; no usarlo para alterar automáticamente la demanda. |
| Producción y abastecimiento | `articulojf` y módulos de producción | Exponer disponible y unidades en proceso para detectar la brecha de abastecimiento. |
| Permisos | `controladores/permisos-modulos.json` | Agregar un módulo nuevo con acciones de ver, editar y publicar/cerrar. |

### Separación obligatoria

El campo existente `articulojf.proyeccion` sirve al flujo operativo de producción/urgencias y está a nivel de SKU. **No se debe usar, sobrescribir ni reinterpretar como proyección comercial.** La proyección nueva se guarda en tablas propias, a nivel de modelo–mes.

## 4. Conceptos y reglas de cálculo

### 4.1 Proyección sugerida vs. oficial

La aplicación calcula una **sugerencia** a partir del historial. El responsable puede aceptarla o editar unidades por cada modelo. El número guardado/publicado es la **proyección oficial**.

Al **guardar borrador o publicar**, se persisten `unidades_sugeridas` y `formula_version` como **snapshot** de lo que vio el responsable en ese momento. Recalcular sugerencias es una acción explícita (solo en líneas en `BORRADOR`, o al corregir sin pisar la oficial hasta que el usuario acepte). La sugerencia nunca publica sola un período.

La primera versión debe usar una recomendación explicable, no IA:

```text
sugerencia_unidades =
  promedio ponderado de ventas netas de los últimos 3, 6 y 12 meses
  + ajuste de estacionalidad del mismo mes en años previos, si hay historia suficiente
```

Parámetros iniciales sugeridos: 50% últimos 3 meses, 30% últimos 6 meses y 20% últimos 12 meses. Si falta historia, se recalculan los pesos con los meses disponibles y la interfaz debe indicarlo. La fórmula definitiva y sus parámetros deben quedar configurables y visibles.

### 4.1.1 Relación sugerencia, factores y oficial

- `unidades_oficiales` se puede editar a mano.
- La línea conserva siempre: sugerencia (snapshot), suma de ajustes de factores y oficial final.
- Si `|unidades_oficiales − (unidades_sugeridas + unidades_ajustes)|` supera un umbral configurable (porcentaje o unidades; pendiente de definir el valor exacto), al guardar/publicar se exige al menos un **factor** o una **observación** con justificación.
- Así la cifra no queda en caja negra, sin forzar que todo ajuste pase por factor cuando la desviación es menor.

### 4.2 Unidades y soles

```text
importe_lista9_proyectado = unidades_oficiales × precio_lista9_snapshot
```

- `unidades_oficiales` admite entero no negativo en la primera versión (incluye 0 como decisión válida).
- Cada modelo tiene **un solo precio** en lista 9; no hay consolidación por SKU.
- `precio_lista9_snapshot` se obtiene cuando se publica cada línea y no cambia aunque luego cambie `preciojf`.
- Si no existe lista 9 para un modelo, **solo esa línea** no se publica; el resto del mes sí. Se muestra como dato pendiente con enlace al precio/modelo. No se bloquea todo el mes por un modelo sin precio.
- Las ventas reales en soles se calculan con venta neta histórica, no con lista 9, para no confundir venta efectivamente facturada con valor planificado.

### 4.2.1 Cobertura de modelos al publicar

- Al crear/cargar un plan se incluyen por defecto **todos los modelos activos**.
- Se puede publicar un mes con algunos modelos en **0** si fue decisión explícita (no “olvidados”).
- Modelos nuevos sin historia: sugerencia 0 / etiqueta “sin historia”; unidades manuales y, al desviarse o estimar sin base, factor Lanzamiento/Otro con justificación.

### 4.3 Indicadores que se muestran antes de decidir

Por modelo y por mes:

1. Ventas netas y unidades de los últimos 12–24 meses.
2. Venta del mismo mes del año anterior y variación porcentual.
3. Promedios móviles 3, 6 y 12 meses; sugerencia y explicación de la fórmula.
4. Precio promedio histórico y precio vigente de lista 9.
5. Stock físico, pedidos comprometidos, stock disponible y unidades en proceso (`taller + servicio + alm_corte + ord_corte`).
6. Brecha referencial: `unidades oficiales − stock disponible − en_proceso`. Es una alerta para planificar; no crea órdenes automáticamente.
7. Margen/costo aprobado, cuando exista, y señal de riesgo si la proyección tiene margen no saludable.
8. Comportamiento por color/talla, zona, vendedor y cliente como información de apoyo (**Fase 2**; no bloquea Fase 1). No modifica la granularidad oficial del plan.

## 5. Factores que modifican o explican la proyección

Cada factor se registra contra una línea modelo–mes y tiene impacto, descripción, evidencia opcional y responsable. Debe haber tipos administrables; como mínimo:

| Tipo | Ejemplos | Tratamiento inicial |
|---|---|---|
| Campaña comercial | descuento, promoción, activación de canal | impacto manual en unidades y justificación. |
| Precio | alza/baja de lista 9 o descuento planificado | registrar precio anterior/nuevo; el responsable decide impacto de demanda. |
| Lanzamiento/relanzamiento | modelo nuevo, nuevo color/talla, reposición | permite estimación manual aun sin historia. |
| Evento/estacionalidad | Fiestas Patrias, Navidad, feria, temporada escolar | impacto manual, con fecha/rango y alcance. |
| Publicidad/redes | pauta Meta/TikTok/Google, influencer, catálogo, mailing | campaña, canal, fechas, inversión y impacto estimado si están disponibles. |
| Disponibilidad | quiebre, demora de material, capacidad, ingreso esperado | puede limitar o ajustar la proyección; no ocultar el riesgo. |
| Mercado | competencia, tendencia, cambio normativo/económico | descripción e impacto manual. |
| Otro | cualquier hecho excepcional | requiere detalle obligatorio. |

El impacto se guarda como ajuste en unidades positivo/negativo, no solo como porcentaje. Puede mostrarse además el porcentaje respecto de la sugerencia. La línea oficial conserva tanto la sugerencia original como el total de ajustes y el valor final para auditoría.

## 6. Flujo de trabajo

```text
Elegir período (ej. jul–dic)
        ↓
Sistema prepara matriz de modelos activos + historial + sugerencia
        ↓
Responsable revisa cada modelo y unidades (factores en Fase 2)
        ↓
Guardar borrador (modificable; persiste sugerencia snapshot)
        ↓
Validar: líneas a publicar con lista 9; 0 permitido; sin lista 9 = solo esa línea queda pendiente
        ↓
Publicar mes o período (líneas PUBLICADO; cabecera BORRADOR → PARCIAL → PUBLICADO)
        ↓
Producción/Logística/Ventas consultan solo líneas oficiales
        ↓
Si se corrige: actualizar la misma fila con motivo obligatorio + auditoría (antes/después)
```

### Estados de la cabecera del período

| Estado | Significado | Acciones permitidas |
|---|---|---|
| `BORRADOR` | Ninguna línea del plan está publicada. | Editar, recalcular sugerencias, eliminar líneas de borrador. |
| `PARCIAL` | Al menos un mes/línea publicado y al menos uno aún en borrador. | Editar solo líneas en borrador; publicar más meses; corregir líneas ya publicadas con motivo. |
| `PUBLICADO` | Todas las líneas del rango del plan están publicadas (o cerradas). | Consultar; corregir con motivo obligatorio. |
| `CERRADO` | Período terminado y congelado para análisis de precisión. | Solo consulta; reapertura solo con permiso excepcional. |

### Estados de línea (`estado_linea`)

| Estado | Quién lo consume |
|---|---|
| `BORRADOR` | Solo el planificador; no es fuente oficial. |
| `PUBLICADO` | Producción, Logística, Ventas y Gerencia. |
| `CERRADO` | Consulta congelada para precisión. |

La verdad operativa es siempre la línea modelo–mes. La cabecera solo organiza el trabajo del rango. Se puede publicar un mes completo o todo el rango; no hay que esperar a diciembre para usar julio. Un nuevo borrador no puede crear otra fila para un `(anio, mes, modelo)` ya publicado.

## 7. Modelo de datos propuesto

Crear una migración nueva: `docs/sql/proyeccion-comercial-modelos.sql`. Los nombres pueden ajustarse al estándar real de la base, pero no se debe guardar JSON de negocio en una sola columna.

```text
proyeccion_comercial_periodojf
  id
  anio_desde, mes_desde, anio_hasta, mes_hasta
  nombre                    -- opcional: "Plan Jul–Dic 2026"
  estado                    -- BORRADOR | PARCIAL | PUBLICADO | CERRADO
  creado_por, creado_en
  actualizado_por, actualizado_en
  publicado_por, publicado_en
  cerrado_por, cerrado_en

proyeccion_comercial_modelojf
  id
  id_periodo
  anio, mes
  modelo
  unidades_sugeridas        -- snapshot al guardar/publicar; no solo cálculo volátil
  unidades_ajustes          -- suma calculada de factores
  unidades_oficiales        -- editable; puede diferir de sugerencia + ajustes
  precio_lista9_snapshot    -- un precio lista 9 por modelo, congelado al publicar
  importe_lista9_proyectado
  formula_version           -- por ejemplo "v1-promedio-ponderado"
  observacion               -- justificación cuando la desviación supera umbral sin factor
  estado_linea              -- BORRADOR | PUBLICADO | CERRADO
  creado_por, creado_en, actualizado_por, actualizado_en, publicado_por, publicado_en
  UNIQUE(anio, mes, modelo) -- una sola fila vigente por modelo–mes (borrador o oficial)

proyeccion_comercial_factorjf
  id
  id_proyeccion_modelo
  tipo
  titulo
  descripcion
  fecha_desde, fecha_hasta
  ajuste_unidades
  impacto_pct               -- derivado o explícito; nunca reemplaza ajuste_unidades
  precio_anterior, precio_nuevo
  canal_publicidad
  inversion_publicidad
  referencia_evidencia      -- URL, código interno o archivo si se habilita adjunto
  creado_por, creado_en, actualizado_por, actualizado_en

proyeccion_comercial_auditoriajf
  id
  id_proyeccion_modelo
  accion                    -- CREAR | ACTUALIZAR | PUBLICAR | CERRAR | REABRIR
  campo
  valor_anterior
  valor_nuevo
  motivo
  usuario
  fecha
```

Reglas de integridad:

- Índice único en `(anio, mes, modelo)`: una sola fila vigente; no hay borrador paralelo a un mes ya publicado.
- Tras publicar, las correcciones actualizan la misma fila; el histórico vive en `proyeccion_comercial_auditoriajf` (opción A: sin versionado de filas).
- La cabecera de período organiza el trabajo semestral (`BORRADOR` / `PARCIAL` / `PUBLICADO` / `CERRADO`); las líneas mensuales son la fuente oficial consumible.
- No borrar líneas o factores publicados: inactivar/corregir mediante auditoría.
- El importe se calcula en backend y se vuelve a validar al guardar/publicar; nunca confiar en un cálculo enviado por JavaScript.
- Guardar auditoría dentro de la misma transacción que la modificación; motivo obligatorio si la línea ya estaba `PUBLICADO` o `CERRADO` (tras reabrir).

## 8. Experiencia de usuario

### 8.1 Pantalla “Plan de proyección”

Filtros: período desde/hasta, marca, categoría/subcategoría, modelo, estado y búsqueda. Acciones Fase 1: crear plan, cargar borrador, recalcular sugerencias, guardar, publicar mes/período, exportar grilla. **Importar** queda fuera de Fase 1.

La tabla central muestra una fila por modelo–mes. Columnas mínimas Fase 1:

```text
Mes | Modelo | Marca/categoría | Hist. mismo mes | Prom. 3/6/12 | Sugerencia |
Unidades oficiales | Lista 9 | S/ proyectados |
Stock disponible | En proceso | Brecha | Estado | Acción/ver detalle
```

La edición masiva se realiza en una grilla rápida. En Fase 1 el detalle de una línea abre un panel con **serie histórica 12–24 meses** y **auditoría**. El contexto rico (color/talla, zona, vendedor, cliente líder) y la gestión completa de factores quedan para **Fase 2**.

### 8.2 Consulta para otras áreas

- **Producción:** unidades oficiales por modelo–mes y brecha; detalle color/talla como referencia en Fase 2. No se genera orden de corte automáticamente.
- **Logística:** demanda, disponible, comprometido, en proceso y alertas de quiebre/cobertura.
- **Ventas:** plan global por modelo contra ventas reales acumuladas del mes; no cambia las metas individuales existentes.
- **Gerencia:** total mensual/semestre en unidades y S/, cobertura de modelos proyectados, cumplimiento real vs proyección y precisión histórica.

## 9. Permisos y ruta

Agregar en `controladores/permisos-modulos.json`:

```json
"proyeccion_comercial_modelos": {
  "ver": [6],
  "editar": [6],
  "publicar": [6],
  "cerrar": [6],
  "reabrir": [6]
}
```

Al inicio, el ID **6** tiene control total para desarrollo y operación temprana. El resto de IDs/roles (gerencia, producción, logística, ventas, etc.) se asignan al final, sin bloquear el arranque.

Ruta propuesta: `proyeccion-comercial-modelos`, bajo Gestión comercial. Producción y Logística deben recibir enlaces de consulta a la misma fuente, no copias de datos ni nuevos cálculos inconsistentes.

## 10. Arquitectura de implementación

Archivos nuevos propuestos:

```text
controladores/proyeccion-comercial-modelos.controlador.php
modelos/proyeccion-comercial-modelos.modelo.php
ajax/proyeccion-comercial-modelos.ajax.php
vistas/modulos/proyeccion-comercial-modelos.php
vistas/js/proyeccion-comercial-modelos.js
vistas/css/proyeccion-comercial-modelos.css
docs/sql/proyeccion-comercial-modelos.sql
docs/comercial/PLAN_PROYECCION_COMERCIAL_MODELOS.md
```

Responsabilidades:

- El modelo centraliza consultas históricas, lista 9, inventario, cálculos de sugerencia y persistencia transaccional.
- El controlador valida rango, permisos, estados y reglas de publicación.
- AJAX devuelve JSON; la vista PHP no debe contener SQL ni reglas de cálculo.
- Reutilizar `ModeloFichaGerencialModelos::mdlResolverTablaMovimientos()` o extraer su resolución segura a una utilidad común. Nunca concatenar directamente un año recibido del navegador.
- Consultar ventas por lote para todos los modelos y meses del período; no ejecutar una consulta por fila de la grilla.

## 11. Alcance por fases

### Fase 0 — Fundaciones y conciliación

- [x] Crear tablas, índices (SQL en `docs/sql/proyeccion-comercial-modelos.sql`; **pendiente ejecutar en BD**).
- [x] Permisos (ID 6), ruta `proyeccion-comercial-modelos`, menú y vista protegida.
- [x] Centralizar regla de venta válida (helpers públicos en ficha gerencial) y consultas de historial por modelo–mes en lote.
- [x] Consulta segura de precio lista 9 vigente (batch).
- [x] Endpoints de solo lectura: `catalogo`, `matriz`, `contextoModelo`, `conciliar`.
- [x] Pantalla de contexto + herramienta de conciliación vs ficha; guía en `docs/comercial/CONCILIACION_PROYECCION_COMERCIAL_FASE0.md`.

### Fase 1 — Plan oficial operativo

- [x] Crear/editar planes mensuales o rangos; cabecera `BORRADOR` / `PARCIAL` / `PUBLICADO` / `CERRADO`.
- [x] Generar líneas con sugerencia (filtro marca o búsqueda obligatoria; máx. 40 modelos por lote).
- [x] Editar unidades oficiales, guardar borrador y publicar por mes o período.
- [x] Congelar lista 9 e importe; sin lista 9 omite solo esa línea.
- [x] Stock / en proceso / brecha en grilla del plan; detalle + auditoría.
- [x] Corregir publicadas con motivo obligatorio (misma fila).
- [x] UX modelo-céntrica (historial + % + factores + guardar/publicar por modelo).
- Consulta oficial por rango disponible vía endpoint `consultaOficial` (UI de consulta simple pendiente de pulir).

**Criterio de aceptación:** el responsable puede publicar el plan de un semestre (o mes a mes), y cada área puede consultar la cifra oficial mensual por modelo en unidades y S/ lista 9, con su fecha, responsable y historial de correcciones.

### Fase 2 — Factores y calidad de decisión

- [x] Registrar factores (tipos del plan) por línea modelo–mes.
- [x] Sumar ajustes, exigir descripción en “Otro”, mostrar % vs sugerencia.
- [x] Exigir factor u observación cuando la desviación supera 10% o 5 unidades.
- [x] Atajo “oficial = sugerencia + ajustes”.
- [ ] Panel de contexto rico (color/talla, zona, vendedor, cliente) — diferido a UX.
- [ ] Alertas finas de stock/margen y export — pendiente.
- [ ] Importación masiva — fuera de alcance.

### Fase 3 — Seguimiento y precisión

- Mostrar venta real diaria/mensual vs proyección oficial.
- Calcular desviación en unidades y soles: `real − proyectado`, `% cumplimiento`, `error absoluto` y `MAPE` solo cuando aplique.
- Cerrar meses y producir ranking de exactitud por modelo, marca/categoría y planificador.
- Mostrar alertas tempranas, sin modificar el plan publicado automáticamente.

### Fase 4 — Planificación asistida

- Configurar estacionalidad, lead time, stock de seguridad, múltiplos y capacidad.
- Proponer necesidades de producción y abastecimiento a partir de la proyección oficial.
- Mantenerlo como simulación/apoyo; cualquier orden de corte o compra se crea explícitamente desde su módulo dueño.

## 12. Riesgos y controles obligatorios

| Riesgo | Control |
|---|---|
| Confundir proyección comercial con `articulojf.proyeccion` | Tablas, rutas, nombres y permisos separados. Nunca actualizar dicho campo. |
| Precio futuro cambia el histórico | Congelar `precio_lista9_snapshot` en publicación. |
| Ventas históricas inconsistentes | Reutilizar regla de movimientos/cabeceras válidas y ejecutar conciliación con documentos reales. |
| Rango semestral incompleto | Publicar/consultar por línea modelo–mes, aunque el plan agrupe seis meses. |
| Modelos nuevos sin historia | Sugerencia cero/etiqueta “sin historia”; exige unidades manuales y factor Lanzamiento/Otro con justificación. |
| Proyección altera operaciones por accidente | Fase 1 es solo consulta para Producción/Logística; no crea movimientos, pedidos u órdenes. |
| Cambios sin trazabilidad | Auditoría transaccional y motivo obligatorio después de publicación. |
| Lentitud | Agregaciones por lote, índices a medir con `EXPLAIN`; crear resumen materializado solo si la medición lo justifica. |
| Dato oficial ambiguo | Indicador visible de estado, fecha de publicación, usuario y última modificación. |

## 13. Lista de inicio para Cursor

1. Leer este documento y `docs/comercial/recetas-modelos/PLAN_FICHA_GERENCIAL_MODELOS.md`.
2. Inspeccionar las consultas existentes en `modelos/ficha-gerencial-modelos.modelo.php`, especialmente ventas mensuales, inventario y resolución de `movimientosjf_AAAA`.
3. Crear y revisar la migración antes de desarrollar interfaz; no modificar tablas existentes de artículos o producción.
4. Implementar permisos, ruta y vista vacía protegida.
5. Implementar primero endpoints de solo lectura: catálogo, contexto histórico e inventario por modelo–mes/rango.
6. Conciliar cifras con modelos reales y documentar los resultados antes de guardar planes.
7. Implementar borrador, publicación y auditoría mediante transacciones.
8. Implementar factores solo después de que la cifra base oficial sea fiable.
9. No integrar IA, órdenes de corte automáticas ni escenarios múltiples en la primera entrega.

## 14. Decisiones de diseño ya cerradas (resumen)

1. Una sola fila vigente por `(anio, mes, modelo)`; borrador no pisa meses ya publicados.
2. Cabecera: `BORRADOR` | `PARCIAL` | `PUBLICADO` | `CERRADO`.
3. Correcciones post-publicación: misma fila + auditoría con motivo (opción A).
4. Oficial editable; desviación grande vs sugerencia + ajustes exige factor u observación.
5. Sugerencia se persiste como snapshot al guardar/publicar; recalcular es acción explícita.
6. Todos los modelos activos; 0 permitido; sin lista 9 bloquea solo esa línea.
7. Un precio lista 9 por modelo; snapshot al publicar la línea.
8. Permisos iniciales: ID 6 con control total; resto de IDs al final.
9. Importar: más adelante (fuera de Fase 1).
10. Fase 1 magra (grilla + histórico + stock/brecha + auditoría); contexto rico y factores en Fase 2.

## 15. Pendientes de configuración que no bloquean el inicio

- Completar IDs/roles adicionales para `ver`, `editar`, `publicar`, `cerrar` y `reabrir` (además del 6).
- Definir el valor exacto del umbral de “desviación relevante” (porcentaje y/o unidades).
- Validar el catálogo exacto de canales publicitarios y si se adjuntarán archivos o solo enlaces/referencias.
- Precio vigente al publicar: el de lista 9 en ese instante (ya confirmado un precio por modelo); si en el futuro hubiera vigencia intradía, se revisa entonces.
