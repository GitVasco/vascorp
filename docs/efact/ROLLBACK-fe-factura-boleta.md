# Rollback — Facturación electrónica (factura/boleta)

Cambios hechos el **2026-07-25** (sesión eFact Legacy CSV 2.1).  
Objetivo: poder volver al comportamiento anterior si eFact/OSE deja de procesar.

Estado al documentar: cambios **sin commit** en el working tree (salvo la columna en BD, que sí se aplicó).

---

## Qué se cambió (resumen)

### A) Guías de remisión en el CSV (FILA 3)

**Antes:** solo se enviaba la guía si la serie era `0003` (guías antiguas). Series electrónicas (`TV01`, `TD01`, `TS01`, `TR01`, etc.) no salían.

**Ahora:** si `ventajf.doc_origen` es una guía válida, se escribe en FILA 3:

- Col A = número (`TV01-00007686`)
- Col B = `09`
- Cols E–G = cuotas (si crédito), como antes
- Col H = `ATTACH_DOC`

**Archivos:**

- `controladores/facturacion.controlador.php`  
  - helper `esGuiaRemisionCsv()`  
  - armado de FILA 3 en `ctrGenerarFEFacBolA` (y filtro viejo en `ctrGenerarFEFacBol`)
- `modelos/facturacion.modelo.php`  
  - `mdlFEFacturaCabA`: `a3`/`b3` solo si `doc_origen` parece guía

### B) Orden de compra del cliente (FILA 7-B)

**Antes:** columna B de FILA 7 iba vacía.

**Ahora:**

1. Columna BD `ventajf.orden_compra` VARCHAR(20) NULL  
2. Campo opcional al facturar (pedidos + guías)  
3. Al generar CSV → FILA 7 col B

**Archivos:**

- `docs/sql/ventajf-orden-compra.sql` (script)
- `modelos/facturacion.modelo.php` — INSERT (`mdlRegistrarDocumento`, `mdlFacturarGuiaV`) + `b7` en `mdlFEFacturaCabA`
- `controladores/facturacion.controlador.php` — `normalizarOrdenCompra()`, persistencia en facturar, FILA 7 con `b7`
- Vistas pedidos: `pedidoscv.php`, `pedidos-confirmados.php`, `pedidos-aprobados.php`, `pedidos-apt.php`, `pedidos-facturados.php`, `pedidos-generados.php`
- `vistas/modulos/facturacion/guias-remision.php`
- `vistas/js/pedidoscv.js`, `vistas/js/facturacion.js`

### C) Fix menor (no es eFact)

- `extensiones/cantidad_en_letras_v2.php` — `number_format((float)$num, …)` para evitar warning PHP al armar monto en letras.

### D) Retención IGV en CSV (FILA 1 — Legacy BP–BR)

**Antes:** columnas BP, BQ, BR vacías.

**Ahora** (con `FE_CSV_RETENCION = true`):

- Solo **factura** (`c1 = 01`), no boleta, no exportación.
- Solo si `clientesjf.agente_retencion = 1`.
- **BP** = base = total precio de venta (mismo valor que BJ).
- **BQ** = factor (`FE_CSV_RETENCION_FACTOR`, default `0.03`).
- **BR** = monto = base × factor (2 decimales).

**Archivos:** `config.php`, `facturacion.controlador.php` (`retencionIgvCsv`, `fila1SufijoDesdeBj`), `mdlFEFacturaCabA` (+ `agente_retencion`).

---

## Kill switches (sin revertir código)

En `controladores/config.php`:

```php
// Guías FILA 3: YA ESTABLE (sin define). Emergencia: define("FE_CSV_GUIAS_RELACIONADAS", false);
define("FE_CSV_ORDEN_COMPRA", true);       // false = oculta campo y FILA 7-B vacía
define("FE_CSV_RETENCION", false);         // true = BP–BR con retención si cliente agente
define("FE_CSV_RETENCION_FACTOR", "0.03"); // factor SUNAT (solo aplica si retención ON)
define("FE_MODELO_COD_UNIDAD", true);      // false = CASE legado por marca (impresión + CSV/XML)
```

Si eFact rechaza en producción/fin de mes:

1. Poner `false` en el flag que falle (OC, retención y/o unidad de medida).
2. Recargar PHP (reiniciar Apache / php-fpm / OPcache).
3. Generar un CSV de prueba y confirmar FILA 1 / FILA 3 / FILA 7-B / unidad en detalle.

No hace falta `git checkout` para apagar estas funciones.

### Tras validar con eFact (dejar permanente)

Cuando la validación pase, pedir a Sistemas/agente:

1. **Quitar** de `config.php` el `define` del feature ya estable (ej. `FE_CSV_RETENCION`, `FE_MODELO_COD_UNIDAD`).
2. El código trata “sin define” como **siempre activo** (`feCsv*Activa()` / `feModeloCodUnidadActiva()` → true).
3. En `config.php` **solo quedan** kill switches de pruebas nuevas aún no validadas.
4. Opcional: dejar `FE_CSV_RETENCION_FACTOR` si se quiere ajustar la tasa sin tocar código.

| Define | OFF hace |
|--------|----------|
| `FE_CSV_GUIAS_RELACIONADAS` *(solo si lo defines)* | Solo guía serie `0003` |
| `FE_CSV_ORDEN_COMPRA` | Sin OC en UI/CSV |
| `FE_CSV_RETENCION` | BP–BR vacíos |
| `FE_MODELO_COD_UNIDAD` | Unidad por CASE marca (ELASTICOS→MTR, TELAS→KGM/KGS, else C62) |

---

## Cómo revertir el código (volver a la versión anterior)

Desde la raíz del repo (`/Users/joel/Proyectos/vascorp`):

### Opción 1 — Revertir solo facturación eFact (recomendado)

```bash
git checkout HEAD -- \
  controladores/facturacion.controlador.php \
  modelos/facturacion.modelo.php \
  extensiones/cantidad_en_letras_v2.php \
  vistas/js/facturacion.js \
  vistas/js/pedidoscv.js \
  vistas/modulos/facturacion/guias-remision.php \
  vistas/modulos/facturacion/pedidoscv.php \
  vistas/modulos/facturacion/pedidos-confirmados.php \
  vistas/modulos/facturacion/pedidos-aprobados.php \
  vistas/modulos/facturacion/pedidos-apt.php \
  vistas/modulos/facturacion/pedidos-facturados.php \
  vistas/modulos/facturacion/pedidos-generados.php

rm -f docs/sql/ventajf-orden-compra.sql
```

Eso deja el código como en el último commit (`HEAD`), sin estos cambios.

### Opción 2 — Revertir TODO lo no commiteado del repo

Solo si no hay otros cambios locales que quieras conservar:

```bash
git restore .
git clean -fd docs/sql/ventajf-orden-compra.sql
```

**Cuidado:** `git restore .` descarta *todos* los cambios sin commit del working tree.

---

## Cómo revertir la base de datos (opcional)

La columna `orden_compra` **no rompe** el procesamiento viejo: el código anterior la ignora.  
Puedes dejarla. Solo bórrala si quieres dejar el esquema limpio:

```sql
-- Solo si ya no usas orden_compra en el código
ALTER TABLE `ventajf` DROP COLUMN `orden_compra`;
```

Script de alta (por si hay que reaplicar): `docs/sql/ventajf-orden-compra.sql`

---

## Después de revertir código

1. Recarga PHP (si usas OPcache/Docker: reinicia el contenedor web o `php-fpm`).
2. Genera de nuevo un CSV de prueba de factura/boleta.
3. En FILA 3 no deberían aparecer guías electrónicas (solo el filtro viejo `0003`).
4. En FILA 7-B debería volver a quedar vacío (`Nro.unidades:…,,fecha,…`).

---

## Si falla el procesamiento con los cambios nuevos

| Síntoma probable | Qué mirar | Rollback sugerido |
|---|---|---|
| eFact rechaza por guía en FILA 3 | Formato serie-correlativo / tipo `09` | Revertir A (archivos controlador + modelo FE) |
| eFact rechaza por orden de compra | Caracteres inválidos en 7-B | Vaciar `orden_compra` o revertir B |
| CSV sin cabecera / nombre `20513613939--.csv` | Comentario SQL roto (ya corregido; no volver a poner `*/` dentro de comentarios) | Restaurar modelo desde HEAD |
| Warning `number_format` | PHP tipado | Mantener fix C o revertir solo ese archivo |

---

## Qué NO se revirtió / no hace falta tocar

- Carpeta `efact/invoice` y `efact/boleta`: son archivos de prueba/salida; borrar CSVs malos a mano si hace falta.
- Relación guía↔factura en BD (`doc_origen` / `doc_destino`): ya existía; estos cambios solo afectan el CSV.
- Documentos ya enviados a SUNAT/OSE no se “deshacen” con el rollback de código.

---

## Checklist rápido antes de producción

- [ ] Confirmar en `controladores/config.php`: `FE_CSV_ORDEN_COMPRA`, `FE_CSV_RETENCION`, `FE_MODELO_COD_UNIDAD` (guías ya permanentes)
- [ ] Generar factura **contado** con guía relacionada → FILA 3 con `SERIE-########,09` (si guías ON)
- [ ] Generar factura **crédito** con guía → FILA 3 con guía + `CUOTA001` + monto + fecha + `ATTACH_DOC`
- [ ] Generar boleta con/sin guía
- [ ] Facturar con OC opcional → FILA 7-B poblada; sin OC → B vacía (si OC ON)
- [ ] Factura a cliente **agente_retencion=1** con `FE_CSV_RETENCION=true` → FILA 1 BP/BQ/BR (base / 0.03 / monto); boleta y no-agente → vacíos
- [ ] Probar kill switch: `false` → sin guías electrónicas / sin OC / sin retención en CSV
- [ ] Confirmar que eFact/OSE acepta el CSV
- [ ] Tras OK de eFact: quitar el `define` validado de `config.php` (queda permanente)
