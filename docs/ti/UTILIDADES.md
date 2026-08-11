# Utilidades

## Propósito

El módulo **Utilidades** concentra tareas operativas que antes se hacían a mano (por ejemplo en Excel). Cada herramienta es un botón en una tarjeta: descripción corta a la vista y detalle completo en la ⓘ.

No pertenece a un área de negocio específica: sirve para mantenimiento y cuadres transversales.

## Acceso

| Concepto | Valor |
|----------|--------|
| Menú | **Utilidades** |
| Ruta | `utilidades` |
| Permiso | sector `utilidades` → módulo `utilidades` |
| Acciones | `ver`, `ejecutar` |
| Usuarios | solo ID **6** |

Fuente: `controladores/permisos-modulos.json`.

## Cómo está armado

- Vista: `vistas/modulos/utilidades/utilidades.php`
- Estilos / JS: `vistas/css/utilidades.css`, `vistas/js/utilidades.js`
- Ajax: `ajax/utilidades.ajax.php`
- Controlador / modelo: `controladores/utilidades.controlador.php`, `modelos/utilidades.modelo.php`

Al agregar una herramienta nueva: tarjeta en la vista (texto corto + ⓘ + botón), acción en ajax/controlador/modelo, y una sección en este documento.

---

## Botón: Cuadrar stock almacén 01

### Qué resuelve

Sustituye el proceso manual de:

1. Conectar a movimientos del año (ODBC / Excel).
2. Filtrar ingresos y salidas del almacén 01.
3. Por artículo: sumar ingresos, restar salidas y comparar con el stock en artículos.
4. Identificar los que no cuadran y actualizar el stock.

### Qué hace en pantalla

1. **Cuadrar** calcula los descuadres y abre un modal.
2. El modal lista artículo, modelo, color, talla, nombre, ingresos, salidas, saldo calculado, stock actual y diferencia.
3. Se pueden marcar filas y **Actualizar seleccionados**: escribe el saldo calculado en la columna `stock` de `articulojf`.

### Reglas de cálculo

| Regla | Detalle |
|-------|---------|
| Tabla de movimientos | `movimientosjf_YYYY` (año actual, zona `America/Lima`) |
| Almacén | solo `almacen = '01'` |
| Ingresos | tipos que empiezan con **E** (cantidad positiva) |
| Salidas | tipos que empiezan con **S**, menos **S01** |
| **S01** | no se toma (guías de remisión; no generan salida de almacén en este cuadre) |
| **E05** | se toma como cantidad × **-1** (devolución / nota de crédito) |
| **E21** | ingreso normal (no se multiplica por -1) |
| Fórmula | `stock_calculado = ingresos − salidas` |
| Comparación | `stock_calculado` vs columna **`stock`** de `articulojf` |
| Exclusiones | marca **ELASTICOS**; modelos que inician con **D0** |
| Orden en lista | código de artículo |
| Actualización | solo `articulojf.stock` (no toca `stock01`) |

### Qué no hace

- No modifica movimientos.
- No actualiza `stock01` ni otros almacenes.
- No incluye artículos de marca ELASTICOS ni modelos `D0*`.

### Ajax

| Acción | Uso |
|--------|-----|
| `descuadresStock01` | Lista descuadres (requiere `ver`) |
| `actualizarStock01` | Actualiza `stock` de los seleccionados (requiere `ejecutar`) |

---

## Botón: Cuadrar servicio / cierre

### Qué resuelve

Sustituye el proceso manual de Excel vía ODBC:

1. Ejecutar la consulta que cruza `articulojf` con servicios abiertos y cierres.
2. Filtrar en Excel los que no cuadran (`servicio` del artículo ≠ servicio abierto + cierre).
3. Armar y correr a mano updates del tipo  
   `UPDATE articulojf SET servicio = '…' WHERE articulo = '…'`.

### Query de referencia (origen Excel)

```sql
SELECT
  a.modelo,
  a.nombre,
  a.cod_color,
  a.color,
  a.cod_talla,
  a.talla,
  a.articulo,
  a.servicio AS servicio_total,
  IFNULL(s.servicio, 0) AS servicio,
  IFNULL(c.cierre, 0) AS cierre
FROM articulojf a
LEFT JOIN (
  SELECT s.articulo, SUM(s.saldo) AS servicio
  FROM servicios_detallejf s
  WHERE s.cerrar = 0
  GROUP BY articulo
) AS s ON a.articulo = s.articulo
LEFT JOIN (
  SELECT c.articulo, SUM(c.cantidad) AS cierre
  FROM cierres_detallejf c
  GROUP BY articulo
) AS c ON a.articulo = c.articulo
```

En el módulo solo se listan filas donde  
`servicio_total <> IFNULL(servicio,0) + IFNULL(cierre,0)`.

### Qué hace en pantalla

1. **Cuadrar** muestra overlay de carga, calcula descuadres y abre el modal.
2. El modal muestra:

| Columna | Significado |
|---------|-------------|
| Artículo / modelo / color / talla / nombre | Identificación del artículo |
| Servicio art. | `articulojf.servicio` (valor actual) |
| Servicio ab. | Suma de `saldo` en servicios abiertos (`cerrar = 0`) |
| Cierre | Suma de `cantidad` en cierres |
| Calculado | `servicio abierto + cierre` (valor que se grabará) |
| Diferencia | `servicio art. − calculado` |

3. Se pueden marcar filas (todas seleccionadas por defecto).
4. **Actualizar seleccionados** pide confirmación, muestra overlay/spinner y deja  
   `articulojf.servicio = servicio_calculado` en los marcados.
5. Al terminar, refresca la lista del modal.

### Reglas de cálculo

| Regla | Detalle |
|-------|---------|
| Servicio artículo | columna `servicio` de `articulojf` |
| Servicio abierto | `SUM(saldo)` en `servicios_detallejf` con `cerrar = 0` |
| Cierre | `SUM(cantidad)` en `cierres_detallejf` |
| Fórmula | `servicio_calculado = servicio_abierto + cierre` |
| Diferencia | `servicio_artículo − servicio_calculado` |
| Filtro | solo filas donde no cuadran |
| Orden | código de artículo |
| Actualización | solo `articulojf.servicio` |

### Qué no hace

- No modifica `servicios_detallejf` ni `cierres_detallejf`.
- No toca stock ni otros campos del artículo.
- No aplica exclusiones de marca ni de modelos (a diferencia del cuadre de stock).

### Ajax

| Acción | Uso |
|--------|-----|
| `descuadresServicio` | Lista descuadres (requiere `ver`) |
| `actualizarServicio` | Actualiza `servicio` de los seleccionados (requiere `ejecutar`) |

---

## UX de carga (ambos botones)

- Al pulsar **Cuadrar**: el botón pasa a “Calculando…” y hay overlay a pantalla completa; el modal se abre al terminar.
- Al confirmar **Actualizar seleccionados**: overlay “Actualizando…” y el botón del modal muestra spinner hasta completar.
