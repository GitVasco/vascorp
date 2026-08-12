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

## Botón: Completar fecha vencimiento

### Qué resuelve

Antes de canje / transferencias a SISCONT, aparecen cargos (`tip_mov = '+'`) sin `fecha_ven` (sobre todo notas de débito antiguas o editadas). Esta utilidad los lista y completa.

### Qué hace en pantalla

1. **Revisar** lista los cargos sin vencimiento válido (NULL, vacío o `0000-00-00`) que sí tienen fecha de documento.
2. El modal muestra tipo, documento, cliente, fecha, vencimiento propuesto (= fecha), monto, saldo y estado.
3. **Completar seleccionados** deja `fecha_ven = fecha` en los marcados.

### Reglas

| Regla | Detalle |
|-------|---------|
| Filtro | `tip_mov = '+'` y `fecha_ven` vacía/nula/`0000-00-00` |
| Requisito | `fecha` del documento válida |
| Actualización | solo `cuenta_ctejf.fecha_ven` |
| Origen del valor | misma fecha del documento (igual que al crear ND) |

### Prevención en facturación

- Al **crear** ND con cte: ya se grababa `fecha_ven = fecha`.
- Al **editar** ND: antes `mdlEditarCuenta` recibía el array sin `fecha_ven` y la dejaba NULL; ahora también envía `fecha_ven = fecha`.

### Ajax

| Acción | Uso |
|--------|-----|
| `cteSinFechaVen` | Lista cargos sin vencimiento (requiere `ver`) |
| `completarFechaVenCte` | Completa `fecha_ven` de los seleccionados (requiere `ejecutar`) |

---

## Botón: Completar fecha origen

### Qué resuelve

En abonos (`tip_mov = '-'`) a veces faltan `fecha_ori` / `fecha_ori_ven`. Se toman del cargo (`tip_mov = '+'`) con el mismo `tipo_doc` + `num_cta`. Necesario para canje / transferencias a SISCONT.

Equivalente al script manual de los últimos 60 días.

### Qué hace en pantalla

1. **Revisar** lista abonos de los últimos 60 días sin origen (o con origen vacío/`0000-00-00`) que sí tienen cargo de referencia.
2. El modal muestra documento, cliente, fecha del abono y las fechas propuestas del cargo.
3. **Completar seleccionados** deja:
   - `fecha_ori = fecha` del cargo
   - `fecha_ori_ven = fecha_ven` del cargo

### Reglas

| Regla | Detalle |
|-------|---------|
| Filtro abono | `tip_mov = '-'`, `fecha` en últimos 60 días |
| Falta origen | `fecha_ori` o `fecha_ori_ven` nula/vacía/`0000-00-00` |
| Cargo | mismo `tipo_doc` + `num_cta`, `tip_mov = '+'` (si hay varios, el de menor `id`) |
| Actualización | solo `fecha_ori` y `fecha_ori_ven` |

### Ajax

| Acción | Uso |
|--------|-----|
| `cteSinFechaOri` | Lista abonos sin origen (requiere `ver`) |
| `completarFechaOriCte` | Completa origen de los seleccionados (requiere `ejecutar`) |

---

## Botón: Completar tipo de cambio

### Qué resuelve

Cuentas del año actual con `tip_cambio` en `0` o `NULL`. Se completa con `totalesjf.cambio_venta` del mismo día (`DATE(fecha)`).

### Qué hace en pantalla

1. **Revisar** lista las cuentas del año sin T/C que sí tienen cambio de venta en totales.
2. El modal muestra documento, movimiento, cliente, fecha, T/C actual y propuesto.
3. **Completar seleccionados** deja `tip_cambio = cambio_venta` del día.

### Reglas

| Regla | Detalle |
|-------|---------|
| Año | año actual (zona `America/Lima`) |
| Filtro | `tip_cambio IS NULL` o `= 0` |
| Fuente | `totalesjf.cambio_venta` del mismo día (si hay varios, el máximo del día) |
| Sin match | no aparece en la lista (no deja T/C en null) |
| Actualización | solo `cuenta_ctejf.tip_cambio` |

### Ajax

| Acción | Uso |
|--------|-----|
| `cteSinTipCambio` | Lista cuentas sin T/C (requiere `ver`) |
| `completarTipCambioCte` | Actualiza T/C de los seleccionados (requiere `ejecutar`) |

---

## Botón: Preparar cte. (secuencia)

### Qué resuelve

Evita correr a mano los botones de cuenta corriente. Ideal antes de transferir a SISCONT.

### Qué hace en pantalla

1. **Ejecutar secuencia** pide confirmación.
2. Abre un modal con pasos y avanza en orden:
   0. Completar T/C en totales (datos-día / API)
   1. Completar fecha vencimiento (todos los pendientes)
   2. Completar fecha origen (todos los pendientes)
   3. Completar tipo de cambio en cte. (todos los pendientes)
3. Cada paso muestra: buscando → completando N → listo / sin pendientes / error.
4. Si un paso falla, se detiene ahí; los botones individuales siguen disponibles.

### Ajax

Reutiliza las mismas acciones de los botones anteriores (sin acción nueva en servidor).

---

## Botón: Completar T/C en totales

### Qué resuelve

Días del año en totales (pantalla Datos diarios) sin `cambio_venta`. Necesario para que luego puedan completarse los T/C de cte. y ventas.

### Qué hace en pantalla

1. **Revisar** lista los días del año (hasta hoy) con T/C venta en 0 o vacío.
2. **Completar seleccionados** consulta la misma API que `/datos-dia` y graba compra/venta.
3. Si un día no tiene TC en la API (finde/feriado), reusa el último día previo que sí tenga.

### Ajax

| Acción | Uso |
|--------|-----|
| `totalesSinTipCambio` | Lista días sin T/C (requiere `ver`) |
| `completarTipCambioTotales` | Consulta API y actualiza totales (requiere `ejecutar`) |

---

## Botón: Preparar ventas (secuencia)

### Qué resuelve

Evita correr a mano los botones de ventas. Ideal antes de transferir a SISCONT.

### Qué hace en pantalla

1. Elige el **periodo** (mes) en la tarjeta de secuencia (aplica a pasos 2–8).
2. **Ejecutar secuencia** pide confirmación.
3. Abre un modal con pasos y avanza en orden:
   0. Completar T/C en totales (datos-día / API)
   1. Tipo de cambio en ventas (año actual, hasta ayer; no usa el periodo)
   2. Cuenta facturas/boletas (S02/S03)
   3. Cuenta POS showroom
   4. Cuenta Culqi
   5. Cuenta NC devolución
   6. Cuenta NC descuento
   7. Cuenta ND flete
   8. Cuenta ND protesto
4. Cada paso muestra: buscando → completando N → listo / sin pendientes / error.
5. Si un paso falla, se detiene ahí; los botones **Revisar** individuales siguen disponibles.

### Ajax

Reutiliza las mismas acciones de los botones de ventas y de T/C en totales (sin acción nueva extra).

---

## Botón: Completar tipo de cambio (ventas)

### Qué resuelve

Ventas del año actual con `tipo_cambio` en `0` o `NULL` y fecha anterior a hoy. Se completa con `totalesjf.cambio_venta` del mismo día.

### Qué hace en pantalla

1. **Revisar** lista las ventas sin T/C que sí tienen cambio de venta en totales.
2. El modal muestra tipo, documento, cliente, fecha, T/C actual y propuesto, total.
3. **Completar seleccionados** deja `tipo_cambio = cambio_venta` del día.

### Reglas

| Regla | Detalle |
|-------|---------|
| Año | año actual (zona `America/Lima`) |
| Fecha | `DATE(fecha) < CURDATE()` (no incluye hoy) |
| Filtro | `tipo_cambio IS NULL` o `= 0` |
| Fuente | `totalesjf.cambio_venta` del mismo día |
| Actualización | solo `ventajf.tipo_cambio` |

### Ajax

| Acción | Uso |
|--------|-----|
| `ventasSinTipCambio` | Lista ventas sin T/C (requiere `ver`) |
| `completarTipCambioVentas` | Actualiza T/C de los seleccionados (requiere `ejecutar`) |

---

## Botón: Completar cuenta contable (ventas)

### Qué resuelve

Asigna la cuenta del plan contable en facturas (`S02`) y boletas (`S03`) del periodo, cuando `cuenta` está vacía.

### Periodo

- Por defecto: **mes actual** (selector `YYYY-MM` en la tarjeta).
- Se puede elegir otro mes (máximo el mes actual) para no tocar periodos futuros.
- El update solo afecta ese rango de fechas.

### Regla de cuenta

| Condición | Cuenta |
|-----------|--------|
| Ubigeo del cliente empieza con `15` o `L` (Lima) | `702211` |
| Resto (provincia / sin ubigeo) | `702212` |

### Qué hace en pantalla

1. Elige periodo y pulsa **Revisar**.
2. Modal con tipo, documento, cliente, ubigeo, zona, fecha, cuenta propuesta y total.
3. **Completar seleccionados** graba la cuenta propuesta.

### Ajax

| Acción | Uso |
|--------|-----|
| `ventasSinCuenta` | Lista S02/S03 sin cuenta del periodo (requiere `ver`) |
| `completarCuentaVentas` | Completa cuenta de los seleccionados (requiere `ejecutar`) |

---

## Botón: Cuenta POS showroom

### Qué resuelve

Sustituye el cruce manual Excel: abonos POS del showroom en cte. → actualizar `ventajf.cuenta = 702213`.

### Criterio en cuenta corriente

| Campo | Valor |
|-------|--------|
| `tip_mov` | `-` |
| Periodo | mes elegido (fecha del abono) |
| `vendedor` | contiene `08` |
| `cod_pago` | `06` o `17` |

### Mapeo tipo_doc → tipo venta

| cte `tipo_doc` | venta `tipo` |
|----------------|--------------|
| `01` | `S03` |
| `03` | `S02` |
| `07` | `E05` |
| `08` | `S05` |

Cruce: `ventajf.documento = cuenta_ctejf.num_cta` + tipo mapeado.

### Qué hace en pantalla

1. Elige periodo y **Revisar**.
2. Modal lista abonos POS con su venta equivalente, cuenta actual y `702213` propuesta.
3. **Completar seleccionados** graba `cuenta = 702213` (solo si aún no la tienen).

### Ajax

| Acción | Uso |
|--------|-----|
| `ventasCuentaPos` | Lista ventas POS pendientes (requiere `ver`) |
| `completarCuentaPosVentas` | Asigna 702213 (requiere `ejecutar`) |

---

## Botón: Cuenta Culqi

### Qué resuelve

Abonos Culqi del showroom → `ventajf.cuenta` según ubigeo (Lima / provincia).

### Criterio en cuenta corriente

| Campo | Valor |
|-------|--------|
| `tip_mov` | `-` |
| Periodo | mes elegido (fecha del abono) |
| `vendedor` | contiene `08` |
| `cod_pago` | `14` |
| `tipo_doc` | `01` o `03` |

### Mapeo y cuenta

| cte `tipo_doc` | venta `tipo` | Cuenta |
|----------------|--------------|--------|
| `01` | `S03` | Lima `702215` / provincia `702216` |
| `03` | `S02` | Lima `702215` / provincia `702216` |

Lima = ubigeo empieza con `15` o `L`.

### Ajax

| Acción | Uso |
|--------|-----|
| `ventasCuentaCulqi` | Lista ventas Culqi pendientes (requiere `ver`) |
| `completarCuentaCulqiVentas` | Asigna 702215/702216 (requiere `ejecutar`) |

---

## Botón: Cuenta NC devolución

### Qué resuelve

Notas de crédito `E05` por devolución (motivos `C1` / `C7`) → cuenta según ubigeo.

### Criterio

| Campo | Valor |
|-------|--------|
| `ventajf.tipo` | `E05` |
| Periodo | mes elegido (`ventajf.fecha`) |
| `notascd_jf.motivo` | `C1` o `C7` |

### Cuenta

| Zona | Cuenta |
|------|--------|
| Lima (`ubigeo` 15… o L…) | `709411` |
| Provincia | `709412` |

### Ajax

| Acción | Uso |
|--------|-----|
| `ventasCuentaNcDev` | Lista NC devolución pendientes (requiere `ver`) |
| `completarCuentaNcDevVentas` | Asigna 709411/709412 (requiere `ejecutar`) |

---

## Botón: Cuenta NC descuento

### Qué resuelve

Notas de crédito `E05` por descuento (motivos **distintos** de `C1` / `C7`) → cuenta según ubigeo.

### Criterio

| Campo | Valor |
|-------|--------|
| `ventajf.tipo` | `E05` |
| Periodo | mes elegido |
| `notascd_jf.motivo` | `NOT IN ('C1', 'C7')` |

### Cuenta

| Zona | Cuenta |
|------|--------|
| Lima | `741101` |
| Provincia | `741102` |

### Ajax

| Acción | Uso |
|--------|-----|
| `ventasCuentaNcDscto` | Lista NC descuento pendientes (requiere `ver`) |
| `completarCuentaNcDsctoVentas` | Asigna 741101/741102 (requiere `ejecutar`) |

---

## Botón: Cuenta ND flete

### Qué resuelve

Notas de débito `S05` por flete del showroom (vendedor contiene `08`) → cuenta según ubigeo.

### Criterio

| Campo | Valor |
|-------|--------|
| `ventajf.tipo` | `S05` |
| Periodo | mes elegido |
| `vendedor` | contiene `08` |

### Cuenta

| Zona | Cuenta |
|------|--------|
| Lima | `75995` |
| Provincia | `75996` |

### Ajax

| Acción | Uso |
|--------|-----|
| `ventasCuentaNdFlete` | Lista ND flete pendientes (requiere `ver`) |
| `completarCuentaNdFleteVentas` | Asigna 75995/75996 (requiere `ejecutar`) |

---

## Botón: Cuenta ND protesto

### Qué resuelve

Notas de débito `S05` por protesto (vendedor **no** contiene `08`) → cuenta según ubigeo.

### Criterio

| Campo | Valor |
|-------|--------|
| `ventajf.tipo` | `S05` |
| Periodo | mes elegido |
| `vendedor` | `NOT LIKE '%08%'` |

### Cuenta

| Zona | Cuenta |
|------|--------|
| Lima | `75991` |
| Provincia | `75992` |

### Ajax

| Acción | Uso |
|--------|-----|
| `ventasCuentaNdProtesto` | Lista ND protesto pendientes (requiere `ver`) |
| `completarCuentaNdProtestoVentas` | Asigna 75991/75992 (requiere `ejecutar`) |

---

## UX de carga

- Al pulsar **Cuadrar** / **Analizar** / **Revisar**: el botón pasa a spinner y hay overlay a pantalla completa; el modal se abre al terminar.
- Al confirmar **Actualizar seleccionados** / **Corregir saldos artículo** / **Completar seleccionados**: overlay y spinner hasta completar.
- **Ejecutar secuencia** (cte o ventas): modal de avance paso a paso (sin overlay global).
