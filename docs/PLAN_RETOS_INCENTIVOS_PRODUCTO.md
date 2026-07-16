# Plan: múltiples incentivos por modelo, color o artículo

**Estado:** implementado en código y SQL; pendiente smoke test en pantalla y checklist de aceptación.  
**Decisión confirmada (2026-07-15):** *Modelo + color* cuenta **todas las tallas** de ese color. Talla/SKU puntual → tipo *Artículo*.  
**Módulo:** Gestión comercial → Metas / retos por vendedor (`metas-retos`).  
**Complementa:** `PLAN_ASIGNACION_MARCAS_VENDEDORES.md` y `PLAN_METAS_RETOS_COMISION_COBRANZA.md` (comisión general por cobranza; incentivos de producto siguen por venta).

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

La tabla `metas_retos_vendedorjf` tiene columnas legacy de un único producto especial (`modelo_especial`, etc.). El cálculo y la UI ya usan solo la tabla hija `metas_retos_incentivos_productojf`. Las columnas antiguas se conservan como respaldo de lectura y no se escriben ni se usan para comisión.

El avance sale de `movimientosjf_AAAA` unido a `articulojf`, con filtro de marcas permitidas del vendedor (`ModeloMetricasComerciales`).

## Reglas funcionales acordadas

1. Un vendedor puede tener cero, uno o muchos incentivos de producto en un mismo período.
2. Cada incentivo se evalúa por separado: cumplir uno no altera la meta ni la comisión de otro.
3. Las comisiones de los incentivos cumplidos son acumulables con la comisión de cobranza, clientes y modelos generales. La comisión general por ventas queda desactivada por política actual (`MR_COMISION_VENTAS_HABILITADA`), pero los incentivos específicos de producto sí pagan sobre su venta objetivo.
4. Todo incentivo respeta la cobertura de grupos de marcas vigente: una venta fuera de cobertura no cuenta aunque coincida con modelo, color o artículo.
5. Tipos de objetivo permitidos:
   - **Modelo:** todos los artículos cuyo `articulojf.modelo` coincide.
   - **Modelo + color:** todos los artículos del modelo y `cod_color` seleccionado; abarca todas sus tallas.
   - **Artículo:** un SKU/código `articulojf.articulo` exacto.
6. La unidad de meta se podrá elegir por incentivo: **unidades** o **docenas** (`cantidad / 12`).
7. La comisión: porcentaje sobre el importe vendido del objetivo. Modo **todo o nada** o **prorrata**.
8. No se permite guardar incentivos duplicados (misma clave tipo+modelo+color+artículo+unidad).
9. Edición solo con permiso `metas_vendedor.editar`.
10. Sin `meta_cantidad > 0` no se genera aporte (evita comisión por meta vacía/cero).

## Modelo de datos

Tabla hija en `docs/sql/metas-retos-incentivos-producto.sql` (ya creada). Validaciones en controlador dentro de transacción; no depender de `CHECK` de MySQL.

## Compatibilidad y migración

1. Tabla hija sin borrar columnas legacy.
2. Migración idempotente: `modelo_especial` → una fila hija tipo `modelo` (solo si `meta_docenas_especial > 0`).
3. Pantalla y cálculos leen solo la tabla hija.
4. Columnas antiguas: respaldo de lectura; no usarlas en cálculos nuevos.

## Interfaz

Bloque **“4) Incentivos por producto”** con tabla editable (agregar/quitar). Listado principal: columna **Incentivos** con resumen `N incentivos · S/` y detalle compacto.

## Cálculo técnico

`ModeloMetricasComerciales::mdlAvanceIncentivosProductoPorVendedorPeriodo()`:

- Una consulta de agregados por vendedor+modelo+color+artículo (marcas permitidas).
- Cruce en PHP con cada incentivo.
- `aporte = venta_objetivo × (comision_pct / 100) × factor`.

Objetivos superpuestos (modelo + modelo_color del mismo modelo, etc.) advierten al guardar y, si se confirman, ambos pagan (acumulable).

Total estimado del período (política vigente):

```text
total = comisión_cobranza + clientes + modelos + incentivos_producto
        (+ ventas solo si MR_COMISION_VENTAS_HABILITADA)
```

## Archivos/componentes

- [x] Crear `docs/sql/metas-retos-incentivos-producto.sql` con tabla, índices, migración y consultas de verificación.
- [x] `modelos/metas-retos.modelo.php`: guardar/listar/eliminar incentivos hijos en transacción y reemplazar la lectura de `modelo_especial`.
- [x] `controladores/metas-retos.controlador.php`: validar el arreglo `incentivos[]`, calcular comisión acumulada y devolver detalle por incentivo.
- [x] `modelos/metricas-comerciales.modelo.php`: agregados por vendedor+modelo+color+artículo con cobertura de marcas.
- [x] `ajax/metas-retos.ajax.php`: admitir/retornar lista de incentivos; guardar con permiso de edición.
- [x] `vistas/modulos/metas-retos.php`: tabla dinámica “Incentivos por producto”.
- [x] `vistas/js/metas-retos.js`: agregar/quitar filas, búsqueda modelo → color y artículo; serializar sin confiar en valores visuales.
- [x] `ajax/tabla-metas-retos.ajax.php`: resumen y detalle de múltiples incentivos + aporte acumulado.
- [x] Reutilizar permisos de `metas_vendedor.editar`.

## Checklist de implementación

### Base y migración

- [ ] Hacer respaldo de `metas_retos_vendedorjf` antes de migrar (operativo).
- [x] Crear la tabla de incentivos hija e índices.
- [x] Migrar el único modelo especial de cada reto existente a una fila hija (SQL idempotente; filtrar meta > 0).
- [ ] Comparar meta, docenas, venta y comisión anterior/nueva para registros migrados (smoke en BD).
- [x] Mantener las columnas antiguas sin escribirlas ni consultarlas para cálculo.

### Catálogos y validación

- [x] Exponer búsqueda de modelos activos.
- [x] Exponer colores disponibles solo para el modelo seleccionado.
- [x] Exponer búsqueda de artículo/SKU y mostrar su modelo, color y marca.
- [x] Validar que todos los objetivos estén dentro de los grupos de marcas del vendedor en la fecha del período.
- [x] Detectar objetivos duplicados y advertir objetivos superpuestos antes de guardar.

### Cálculo y presentación

- [x] Calcular unidades, docenas e importe por vendedor+modelo+color+artículo en lote.
- [x] Reutilizar el filtro de marcas permitidas y reglas de notas de crédito/devolución.
- [x] Calcular factor y aporte individual por incentivo (sin pagar si meta ≤ 0).
- [x] Sumar aportes individuales al total estimado de comisiones.
- [x] Mostrar detalle por incentivo en el listado (modal: edición sin columna de avance en vivo).
- [x] Mantener intactos los cálculos de cobranza, clientes y modelos generales; ventas desactivadas por config.

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

## Única decisión de negocio

**Confirmada:** para incentivar “un color especial” de un modelo se cuentan **todas las tallas de ese color** (tipo *Modelo + color*). Si se desea una talla/SKU particular, se usa el tipo *Artículo*.
