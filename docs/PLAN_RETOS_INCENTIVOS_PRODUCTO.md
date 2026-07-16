# Plan: múltiples incentivos por modelo, color o artículo

**Estado:** listo para aplicar SQL + probar en BD.  
**Decisión confirmada (2026-07-15):** *Modelo + color* cuenta **todas las tallas** de ese color. Talla/SKU puntual → tipo *Artículo*.  
**Módulo:** Gestión comercial → Metas / retos por vendedor (`metas-retos`).  
**Complementa:** `PLAN_ASIGNACION_MARCAS_VENDEDORES.md`.

## Resultado esperado

En un mismo mes, a un vendedor se le podrán configurar varios incentivos de producto. Cada incentivo tendrá su propia meta, comisión y avance, y solo considerará ventas de marcas permitidas para ese vendedor.

Ejemplos:

| Incentivo | Objetivo | Medición |
|---|---|---|
| 1 | Modelo `M100` | 12 docenas de cualquier color del modelo |
| 2 | Modelo `M100`, color rojo | 6 docenas del modelo solo en rojo, sin contar otros colores |
| 3 | Artículo `M100-ROJO-TALLA-M` | 24 unidades de ese SKU exacto |

Los tres pueden convivir en el mismo reto mensual y sus comisiones se calculan de forma independiente y acumulable.

## Situación actual

La tabla `metas_retos_vendedorjf` tiene una sola configuración de producto especial: `modelo_especial`, `meta_docenas_especial`, `comision_modelo_esp_pct` y `cumplimiento_modelo_esp`. Por ello el formulario permite elegir un único modelo por vendedor/mes.

El avance ya se obtiene desde `movimientosjf_AAAA` unido a `articulojf`, y las métricas de cobertura ya filtran las marcas permitidas del vendedor. La mejora debe reutilizar esa base, no crear un cálculo paralelo.

## Reglas funcionales acordadas/propuestas

1. Un vendedor puede tener cero, uno o muchos incentivos de producto en un mismo período.
2. Cada incentivo se evalúa por separado: cumplir uno no altera la meta ni la comisión de otro.
3. Las comisiones de los incentivos cumplidos son acumulables con las de monto, clientes y modelos generales.
4. Todo incentivo respeta la cobertura de grupos de marcas vigente: una venta fuera de cobertura no cuenta aunque coincida con modelo, color o artículo.
5. Tipos de objetivo permitidos:
   - **Modelo:** todos los artículos cuyo `articulojf.modelo` coincide.
   - **Modelo + color:** todos los artículos del modelo y `cod_color` seleccionado; abarca todas sus tallas.
   - **Artículo:** un SKU/código `articulojf.articulo` exacto.
6. La unidad de meta se podrá elegir por incentivo: **unidades** o **docenas** (`cantidad / 12`). Así un incentivo por modelo puede conservar la práctica actual de docenas y uno por artículo puede definirse en unidades.
7. La comisión mantiene la regla actual: porcentaje sobre el importe vendido que cumple el objetivo. El modo de cumplimiento podrá ser **todo o nada** o **prorrata**.
8. No se permitirá guardar incentivos duplicados para el mismo vendedor/período/objetivo/unidad. La misma combinación no debe pagar dos veces por error.
9. Los incentivos solo se pueden crear o editar por el propietario autorizado, igual que grupos y asignaciones de marcas.

## Modelo de datos propuesto

Mantener `metas_retos_vendedorjf` para las metas generales del vendedor y crear una tabla hija, por ejemplo en `docs/sql/metas-retos-incentivos-producto.sql`:

```sql
CREATE TABLE metas_retos_incentivos_productojf (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    id_meta_reto            INT NOT NULL COMMENT 'metas_retos_vendedorjf.id',
    tipo_objetivo           VARCHAR(20) NOT NULL COMMENT 'modelo | modelo_color | articulo',
    modelo                  VARCHAR(50) NULL COMMENT 'Obligatorio para modelo y modelo_color',
    cod_color               VARCHAR(30) NULL COMMENT 'Obligatorio para modelo_color',
    articulo                VARCHAR(80) NULL COMMENT 'Obligatorio para articulo',
    unidad_meta             VARCHAR(15) NOT NULL DEFAULT 'docenas' COMMENT 'unidades | docenas',
    meta_cantidad           DECIMAL(12,2) NOT NULL,
    comision_pct            DECIMAL(8,2) NOT NULL DEFAULT 0 COMMENT '% sobre venta objetivo',
    cumplimiento            VARCHAR(20) NOT NULL DEFAULT 'todo_nada' COMMENT 'todo_nada | prorrata',
    orden                   SMALLINT NOT NULL DEFAULT 0,
    observacion             VARCHAR(255) NULL,
    usuario                 INT NULL,
    fecreg                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecmod                  DATETIME NULL,
    KEY idx_mrip_reto (id_meta_reto),
    KEY idx_mrip_modelo_color (modelo, cod_color),
    KEY idx_mrip_articulo (articulo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Incentivos de producto de una meta/reto mensual por vendedor';
```

Validaciones obligatorias en backend, antes de guardar:

- `id_meta_reto` debe corresponder al vendedor, año y mes editados.
- `tipo_objetivo` debe ser uno de los tres valores permitidos.
- Para `modelo`: modelo existente y sin `cod_color`/`articulo`.
- Para `modelo_color`: modelo existente, color existente para ese modelo y sin `articulo`.
- Para `articulo`: artículo existente; el backend obtiene de catálogo su modelo, marca y color solo para mostrar, no los recibe como verdad desde el navegador.
- `meta_cantidad > 0`, comisión entre `0` y `100`, unidad y modo válidos.
- No duplicar el mismo objetivo dentro de un reto. En la práctica, validar con una clave normalizada por tipo + modelo + color + artículo + unidad.
- Verificar que el objetivo pertenezca a una marca permitida para ese vendedor en el período. Esta validación evita crear incentivos imposibles de cumplir.

No se debe depender de `CHECK` para las reglas de forma: la versión de MySQL puede no aplicarlos de forma uniforme. Las validaciones deben vivir en controlador/modelo dentro de una transacción.

## Compatibilidad y migración

1. Crear la tabla hija sin borrar ni renombrar las columnas actuales de `metas_retos_vendedorjf`.
2. Migrar cada reto que tenga `modelo_especial` a una fila hija con:
   - `tipo_objetivo = 'modelo'`;
   - `modelo = modelo_especial`;
   - `unidad_meta = 'docenas'`;
   - `meta_cantidad = meta_docenas_especial`;
   - `comision_pct = comision_modelo_esp_pct`;
   - `cumplimiento = cumplimiento_modelo_esp`.
3. Comparar el avance y comisión de cada registro migrado contra el cálculo anterior. Deben coincidir exactamente, salvo redondeos explícitamente definidos a dos decimales.
4. Cambiar la pantalla y los cálculos para leer solo la tabla hija.
5. Conservar las columnas antiguas durante al menos un cierre mensual como respaldo de lectura; después dejarlas obsoletas, sin usarlas para cálculos nuevos. No eliminarlas hasta contar con respaldo y aprobación.

## Interfaz propuesta

Reemplazar el bloque **“4) Modelo especial (beneficio)”** por **“4) Incentivos por producto”**.

La sección tendrá una tabla editable:

| Tipo | Objetivo | Unidad | Meta | Comisión | Cumplimiento | Avance | Acción |
|---|---|---:|---:|---:|---|---:|---|
| Modelo | M100 | Docenas | 12 | 2 % | Todo o nada | 8.5 | Editar / quitar |
| Modelo + color | M100 · Rojo | Docenas | 6 | 3 % | Prorrata | 4 | Editar / quitar |
| Artículo | SKU-123 | Unidades | 24 | 4 % | Todo o nada | 24 | Editar / quitar |

Flujo para agregar una fila:

1. Pulsar **Agregar incentivo**.
2. Elegir el tipo de objetivo.
3. Buscar modelo, luego color si aplica; o buscar artículo si se eligió SKU.
4. Elegir unidades o docenas, ingresar meta, porcentaje y modo.
5. Mostrar la marca y grupo del objetivo como solo lectura, y advertir si no está permitido para el vendedor.
6. Guardar toda la cabecera y las filas en una única transacción.

El listado principal de metas/retos debe cambiar su columna “Especial” por “Incentivos”. Debe mostrar un resumen compacto (`3 incentivos · S/ estimado`) y un detalle expandible/tooltip con cada objetivo, su avance y aporte. No intentar mostrar muchas barras completas dentro de una sola celda.

## Cálculo técnico

Crear o extender un método común de métricas, por ejemplo:

```php
ModeloMetricasComerciales::mdlAvanceIncentivosProductoPorVendedorPeriodo($anio, $mes, $incentivos)
```

La consulta debe agrupar las líneas de `movimientosjf_AAAA` por vendedor, modelo, `cod_color` y artículo, usando:

- `SUM(m.cantidad)` para unidades;
- `SUM(m.cantidad) / 12` para docenas;
- `SUM(m.total)` como importe de venta que sirve de base para el porcentaje de comisión;
- las mismas clases de documento válidas que usa el módulo hoy;
- `ModeloMetricasComerciales::sqlLineaMarcaPermitida('m', 'a')` para no incluir marcas fuera de cobertura;
- exclusión de anulados y las reglas vigentes para devoluciones/notas de crédito.

Evitar una consulta por incentivo. Obtener los agregados del mes en una consulta y cruzarlos en PHP con las filas de incentivos, o usar una consulta por lote con los objetivos activos. Esto evita degradar el listado cuando haya muchos vendedores e incentivos.

Para cada incentivo:

```text
avance_unidades = suma de cantidades de las líneas que coinciden
avance_meta     = avance_unidades o avance_unidades / 12, según unidad_meta
venta_objetivo  = suma de m.total de esas mismas líneas
factor          = todo_nada ? (avance_meta >= meta ? 1 : 0)
                              : min(1, avance_meta / meta)
aporte          = venta_objetivo × (comision_pct / 100) × factor
```

El porcentaje solo se calcula sobre la venta del objetivo, no sobre toda la venta del vendedor. Si dos incentivos distintos incluyen la misma línea (por ejemplo uno por modelo y otro por el color rojo de ese modelo), la línea puede contribuir al avance y comisión de ambos: es acumulable por decisión funcional. La pantalla debe advertir esa superposición al guardar para que sea intencional.

## Archivos/componentes que Cursor debe modificar

- [x] Crear `docs/sql/metas-retos-incentivos-producto.sql` con tabla, índices, migración y consultas de verificación.
- [x] `modelos/metas-retos.modelo.php`: guardar/listar/eliminar incentivos hijos en transacción y reemplazar la lectura de `modelo_especial`.
- [x] `controladores/metas-retos.controlador.php`: validar el arreglo `incentivos[]`, calcular comisión acumulada y devolver detalle por incentivo.
- [x] `modelos/metricas-comerciales.modelo.php`: agregar agregados por vendedor+modelo+color+artículo, siempre con cobertura de marcas permitida.
- [x] `ajax/metas-retos.ajax.php`: admitir/retornar lista de incentivos y proteger todas las acciones con permiso de edición.
- [x] `vistas/modulos/metas-retos.php`: tabla dinámica “Incentivos por producto”.
- [x] `vistas/js/metas-retos.js`: agregar/quitar filas, búsqueda dependiente modelo → color y búsqueda de artículo; serializar el arreglo sin confiar en valores visuales.
- [x] `ajax/tabla-metas-retos.ajax.php`: resumen y detalle de múltiples incentivos, así como el aporte total acumulado.
- [x] Reutilizar los permisos actuales de `metas_vendedor.editar`; confirmar que ese permiso quede restringido al propietario autorizado.

## Checklist de implementación

### Base y migración

- [ ] Hacer respaldo de `metas_retos_vendedorjf` antes de migrar.
- [ ] Crear la tabla de incentivos hija e índices.
- [ ] Migrar el único modelo especial de cada reto existente a una fila hija.
- [ ] Comparar meta, docenas, venta y comisión anterior/nueva para todos los registros migrados.
- [ ] Mantener las columnas antiguas sin escribirlas ni consultarlas hasta terminar el período de observación.

### Catálogos y validación

- [ ] Exponer búsqueda de modelos activos.
- [ ] Exponer colores disponibles solo para el modelo seleccionado.
- [ ] Exponer búsqueda de artículo/SKU y mostrar su modelo, color y marca.
- [ ] Validar que todos los objetivos estén dentro de los grupos de marcas del vendedor en la fecha del período.
- [ ] Detectar objetivos duplicados y advertir objetivos superpuestos antes de guardar.

### Cálculo y presentación

- [ ] Calcular unidades, docenas e importe por vendedor+modelo+color+artículo en lote.
- [ ] Reutilizar el filtro de marcas permitidas y reglas de notas de crédito/devolución.
- [ ] Calcular factor y aporte individual por incentivo.
- [ ] Sumar aportes individuales al total estimado de comisiones.
- [ ] Mostrar detalle por incentivo en el listado y en el modal.
- [ ] Mantener intactos los cálculos de monto, clientes, modelos generales y cobranza.

### Pruebas de aceptación

- [ ] Un vendedor con tres incentivos ve los tres en el mismo mes y puede guardarlos en una sola operación.
- [ ] Un incentivo por modelo suma todos sus colores y tallas.
- [ ] Un incentivo por modelo+color suma solo ese color y todas sus tallas.
- [ ] Un incentivo por artículo suma solo el SKU exacto.
- [ ] Una venta de una marca fuera de cobertura no suma a ningún incentivo.
- [ ] Una devolución reduce unidades/docenas e importe del mismo objetivo según las reglas comerciales actuales.
- [ ] La migración de un reto antiguo con un modelo especial da el mismo avance y comisión que antes.
- [ ] Dos incentivos superpuestos muestran advertencia y, si se confirman, ambos calculan su aporte.
- [ ] Un incentivo no cumplido en modo todo o nada no genera comisión; en prorrata genera el aporte proporcional.
- [ ] La suma de aportes por producto coincide con el total mostrado en el panel.
- [ ] Las consultas se validan con `EXPLAIN` y no se ejecuta una consulta independiente por cada incentivo.

## Única decisión de negocio para confirmar

**Confirmada:** para incentivar “un color especial” de un modelo se cuentan **todas las tallas de ese color** (tipo *Modelo + color*). Si se desea una talla/SKU particular, se usa el tipo *Artículo*.