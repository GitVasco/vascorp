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

## Botón: Tracking modelo (producción)

### Qué resuelve

Diagnóstico (y corrección de espejos) de un modelo en el flujo de producción:

`orden de corte → almacén de corte → taller/servicio → cierre → ingresos E20`

Útil cuando hay saldos “fantasma” (por ejemplo `alm_corte` cargado sin pasar por orden de corte / corte).

### Qué hace en pantalla

1. Ingresas el código de modelo (ej. `10400`) y pulsas **Analizar**.
2. Se abre un modal con:
   - **Resumen**: totales de columnas en `articulojf`, documentos por etapa, más **Inicio corte**, **En proceso**, **Ingresos E20** y **Brecha**.
   - **Inconsistencias**: solo los problemas detectados.
   - **Detalle por artículo**: comparación actual vs calculado, más inicio/ingresos/brecha.
3. Con permiso `ejecutar`: botón **Corregir saldos artículo** sincroniza solo columnas de `articulojf` según documentos y re-analiza.

### Conservación corte → ingresos

```text
inicio_corte = SUM(almacencorte_detallejf.cantidad)
en_proceso   = alm_corte_calc + taller_calc + servicio_calc
ingresos_e20 = SUM(E20) del año actual en movimientosjf_YYYY
brecha       = inicio_corte − (en_proceso + ingresos_e20)
```

La brecha se muestra y alerta (`BRECHA_CORTE_INGRESOS`). **No** se fuerza creando ni borrando E20.

### Cadena servicio → cierre → ingreso

Tabla aparte en el modal. Por artículo:

| Campo | Origen |
|-------|--------|
| Serv.orig | `SUM(servicios_detallejf.cantidad)` |
| Serv.ab | `SUM(saldo)` con `cerrar = 0` |
| Cierre ini | `SUM(cierres_detallejf.inicio)` (cantidad al crear el cierre) |
| Cierre pend | `SUM(cierres_detallejf.cantidad)` (pendiente de ingresar) |
| E20 cierre | `SUM(E20)` del año con `idcierre > 0` |

Reglas (Δ ≠ 0 = descuadre, solo diagnóstico):

```text
Δ Serv→Cierre = Serv.orig − (Serv.ab + Cierre ini)
Δ Cierre→Ing  = Cierre ini − (Cierre pend + E20 cierre)
Δ Cadena      = Serv.orig − (Serv.ab + Cierre pend + E20 cierre)
```

Así se ve si el problema está al pasar a cierre o al ingresar a almacén.

### Corrección de cadena (mismo botón)

Orden de corrección:

1. `cierres_detallejf.inicio` = `cantidad` pendiente + `SUM(E20)` con ese `idcierre`
2. Por artículo: `servicios_detallejf.saldo` se redistribuye para que el abierto total sea `max(0, SUM(cantidad) − SUM(inicio cierres))` (quita pendiente fantasma; si una línea queda en 0 → `cerrar=1`). **No infla** `cantidad` origen.
3. Espejos `articulojf` + saldos `entaller` externo

No modifica movimientos E20 ni stock.

### Reglas de detección

| Código | Significado |
|--------|-------------|
| `ORD_CORTE_DESCUADRE` | `ord_corte` ≠ suma de `saldo` en `detalles_ordencortejf` |
| `ALM_CORTE_DESCUADRE` | `alm_corte` ≠ suma de `saldo_taller` en `almacencorte_detallejf` |
| `TALLER_DESCUADRE` | `taller` ≠ suma de `saldo` en envíos a **taller interno** (`sectorjf.tipo = 0` o `VC`; excluye servicio externo) |
| `SERVICIO_DESCUADRE` | `servicio` ≠ servicios abiertos (`cerrar=0`) + cierres |
| `BRECHA_CORTE_INGRESOS` | inicio corte ≠ en proceso + ingresos E20 (solo alerta) |
| `CORTE_SIN_OC` | Detalle de almacén corte sin orden de corte válida |
| `ENVIO_SIN_CORTE` | Envío a taller sin vínculo a detalle de almacén corte |
| `SERVICIO_SIN_ENVIO` | Detalle de servicio sin `cabecera_taller` válida |
| `MODELO_SIN_OC_CON_CORTE` | Hay corte/`alm_corte` y cero detalle de orden de corte |
| `MODELO_SIN_DOC_CORTE` | `alm_corte > 0` sin filas en `almacencorte_detallejf` |

### Corrección (`corregirSaldosModelo`)

Por cada artículo del modelo deja:

| Columna | Valor calculado |
|---------|-----------------|
| `ord_corte` | `SUM(detalles_ordencortejf.saldo)` |
| `alm_corte` | `SUM(saldo_taller)` donde `saldo_taller > 0` |
| `taller` | `SUM(saldo)` solo envíos a taller **interno** (`tipo = 0` / `VC`) |
| `servicio` | servicios abiertos + cierres |

Además, por cada envío a **servicio externo** (`entaller_cabjf` con `sectorjf.tipo <> 0`):

| Campo | Valor |
|-------|--------|
| `entaller_cabjf.saldo` | `SUM(servicios_detallejf.saldo)` abiertos ligados (`cabecera_taller = id`); **0** si no hay servicio abierto |
| `estado` | `1` si saldo queda 0; `0` si queda pendiente |

En pantalla: columnas **Ent.ext** / **Ent.ext calc** (naranja si se corregirán).

Requiere permiso `ejecutar`. Si `alm_corte` era fantasma sin documentos, queda en 0.

**No** crea cortes ni ingresos; la brecha histórica puede seguir.

### Ajax

| Acción | Uso |
|--------|-----|
| `trackingModelo` | Analiza el modelo (requiere `ver`) |
| `corregirSaldosModelo` | Sincroniza columnas espejo (requiere `ejecutar`) |

### Qué no hace

- No modifica `servicios_detallejf`, `cierres_detallejf`, `entaller_cabjf` ni movimientos.
- No crea ni borra ingresos E20 (no toca stock).
- No recrea órdenes de corte ni cortes.
- No analiza todos los modelos a la vez (un código por corrida).

---

## UX de carga

- Al pulsar **Cuadrar** / **Analizar**: el botón pasa a “Calculando…” / spinner y hay overlay a pantalla completa; el modal se abre al terminar.
- Al confirmar **Actualizar seleccionados** / **Corregir saldos artículo**: overlay y spinner hasta completar.
