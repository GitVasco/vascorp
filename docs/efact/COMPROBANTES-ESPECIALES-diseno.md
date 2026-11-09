# Comprobantes especiales — módulo estándar (tipo actual: GRATUITA) — Diseño

**Emisor:** Corporación Vasco S.A.C. — RUC 20513613939
**Fecha:** 9 de noviembre de 2026
**Estado:** **implementado en rama `joel`, pendiente de probar con la BD y eFact** (guía: `pruebas-comprobantes-especiales/GUIA-PRUEBA.md`). Pendientes de decisión en §9.

**Implementado:** `docs/sql/comprobantes-especiales.sql`, `modelos/comprobantes-especiales.modelo.php`, `controladores/comprobantes-especiales.controlador.php`, `ajax/comprobantes-especiales.ajax.php`, vistas `comprobantes-especiales.php` / `comprobantes-especiales-crear.php`, `vistas/js/comprobantes-especiales.js`, `vistas/reportes_ticket/impresion_comprobante_especial_html.php` y `impresion_comprobante_especial.php` y cableado en `index.php`, `plantilla.php`, `menu.php`.
**Diferencias frente al diseño:** (1) los ítems son solo manuales (sin buscador de artículos); (2) acceso por `COMPROBANTES_ESPECIALES_USUARIOS` (id de sesión), por defecto 70 y 6; (3) inafecto desactivado salvo `GRATUITAS_PERMITE_INAFECTO`; (4) se agregó el estado `BAJA` (registro manual de que se dio de baja en eFact); (5) el total en letras del obsequio es fijo "CERO Y 00/100 SOLES" — **no** se modificó `convertir()` del flujo normal; (6) series provisionales BG01 / FG01; (7) máximo 7 días de antigüedad en la fecha de emisión.
**Base técnica:** `ITEMS-GRATUITOS-obsequios-analisis.md` (brechas y reglas del manual Legacy CSV 2.1).
**Pendientes (cuenta por cobrar, prueba con eFact, registro de ventas, SISCONT):** `COMPROBANTES-ESPECIALES-pendientes.md`.
**Patrón a copiar:** `GRE-manual-diseno.md` (módulo aislado, tablas propias, CSV propio, misma carpeta de salida).

---

## 1. Objetivo y alcance

Emitir boletas (trabajadores, clientes sin RUC) y facturas (proveedores/clientes con RUC) **solo de obsequios**, con IGV no cobrado y total 0.00, **sin pasar por pedidos, ventas, artículos, stock ni cobranza**.

**Dentro del alcance:** comprobante 100 % gratuito (todas las líneas son obsequio).
**Fuera del alcance (por ahora):** comprobante mixto gravado + gratuito (como el ejemplo Memory Kings). Eso es una *bonificación* dentro de una venta (afectación 15) y debe resolverse en el flujo normal más adelante, si se necesita.

## 2. Ejemplos de Contabilidad (referencia visual)

| | Memory Kings | Autonort Trujillo | Virazon |
|---|---|---|---|
| Comprobante | Boleta B138-010246 (30/06/2025) | Boleta B025-0002035 (31/07/2023) | Boleta EB01-720 (31/12/2025) |
| Receptor | Persona con DNI | Colaboradora con DNI | Trabajador con DNI |
| Ítem gratuito | "BONIFICACION - TRANSFERENCIA GRATUITA" y "Obsequio – Kit con logo Genius" (V.V. 15.38) | "OBSEQUIO (CASACA) POR CUMPLEAÑOS DE COLABORADORES CORRESPONDIENTE AL MES JULIO 2023", 1 UNID, valor 38.59 | "CANASTA NAVIDEÑA ***** ENTREGA A TRABAJADORES *****", 1 UNID, valor 67.80 |
| Op. Gravadas | 5,193.64 | 0.00 | 0.00 |
| **Op. Gratuitas** | **15.38** | **38.59** | **67.80** |
| IGV mostrado | 934.86 (solo de lo gravado) | **6.95** (= 18 % del valor gratuito, informativo) | 0.00 |
| **Importe total** | 6,128.50 | **0.00** | **0.00** |
| Total en letras | — | "CERO Y 00/100 SOLES" | "CERO Y 00/100 SOLES" |
| Leyenda | (en línea del ítem) | "TRANSFERENCIA GRATUITA" | "TRANSFERENCIA GRATUITA DE UN BIEN Y/O SERVICIO PRESTADO GRATUITAMENTE" |
| Condición | CONTADO | CONTADO | — |

**Lo que confirman los ejemplos**
1. Un comprobante solo de obsequios con **total 0.00 existe y es normal** (Autonort, Virazon).
2. El valor del regalo se muestra en **Op. Gratuitas** y no suma al total (coincide con Y del manual).
3. El IGV informativo del regalo **puede mostrarse en la impresión** (Autonort 6.95) o no (Virazon): es decisión de formato, no de SUNAT. En el CSV va en **AO**.
4. La descripción dice **a quién y por qué** ("por cumpleaños de colaboradores", "entrega a trabajadores"). Conviene una descripción estandarizada.
5. El monto en letras debe ser "CERO Y 00/100 SOLES": **nuestro `convertir(0)` hoy devuelve " Y 00/100 SOLES"** → corregir.
6. Todos los ejemplos son **boletas a personas con DNI**. **No hay ejemplo de factura**; pedirlo a Contabilidad para proveedores/clientes con RUC.

## 2b. CSV de ejemplo de eFact (carpeta `ejemplos-gratuitos/`)

Archivos: `Gratuita con igv/20551093035-01-FD02-9030.csv` (afectación **14**) y `Gratuita sin igv/20551093035-01-FD01-000731.csv` (afectación **32**). Son facturas de otro emisor (RUC 20551093035), con 2 líneas y total 0.00.

**Aviso de layout:** los ejemplos usan el layout **Factura-CSV 2021** (10 filas de cabecera, fecha `YYYY-MM-DD`, leyendas en fila 7, ítems desde fila 9, líneas con I = base y J = IGV). **Nuestro CSV usa otro layout** (9 filas, `dd/mm/yyyy`, leyendas en fila 6, ítems desde fila 8, I = J = IGV). Por eso se copian los **valores y conceptos**, no las posiciones. Lo que es igual en ambos layouts se marca como ✔.

| Concepto | Valor en el ejemplo eFact | Posición ejemplo (2021) | Posición nuestra | Estado |
|---|---|---|---|---|
| Importe total | `0.00` | N1 | N | ✔ eFact acepta total 0.00 |
| IGV cobrado (cabecera) | `0.00` | E1, F1 | E, F | ✔ |
| Forma de pago | `Contado` | O1 | O | ✔ |
| Tipo de operación | `0101` | Q1 | Q | ✔ |
| **Total op. gratuitas** | `70.00` (10 + 60) | **Y1** | **Y** | ✔ misma columna en ambos manuales |
| IGV de gratuitas (informativo) | `12.60` (con IGV) / `0.00` (sin IGV) | **T1** | **AO** (Legacy) | ⚠ posición distinta, probar |
| Total impuestos / valor / precio | `0.00` | BB1, BC1, BD1 | BH, BI, BJ | ⚠ equivalentes en nuestro layout |
| N.º de líneas | `2` | AL1 | AS | ⚠ equivalente |
| **Leyenda 1002** | `TRANSFERENCIA GRATUITA DE UN BIEN Y/O SERVICIO PRESTADO GRATUITAMENTE` | B de la fila de leyendas | B de FILA 6 | ✔ texto confirmado |
| Unidad | `ZZ` (servicio) | B (línea) | B | ✔ (`C62` para bienes) |
| Precio unitario | `0.00` | E (línea) | E | ✔ |
| Código de precio | `01` | F (línea) | F | ✔ **confirmado `01`** |
| Valor referencial unitario | `10.00` / `20.00` | G (línea) | G | ✔ |
| Código valor referencial | `02` | H (línea) | H | ✔ |
| Afectación | `14` (con IGV) / `32` (sin IGV) | K (línea) | K | ✔ |
| Código de tributo | `9996` | L (línea) | L | ✔ |
| Porcentaje IGV | `18.00` (afect. 14) / `0.00` (afect. 32) | M (línea) | M | ✔ |
| IGV informativo de la línea | `1.80`, `10.80` (= 18 % de 10 y 60) / `0.00` | J (línea) | I y J (en nuestro layout ambas llevan IGV) | ⚠ |
| Valor unitario / valor de venta | `0.00` / `10.00`, `60.00` (= ref × cant) | T, U (línea) | T, U | ✔ |
| Base | `10.00`, `60.00` | I y AL (línea) | X (línea) | ⚠ |

**Conclusiones**
1. **Se confirman** los valores de §6: F = `01`, G/H = referencial/`02`, L = `9996`, E = 0, T = 0, U = ref × cantidad, Y = total gratuito, leyenda en B, total 0.00 válido.
2. **"Con IGV" y "sin IGV" no son una opción de impresión: son dos afectaciones distintas.** Con IGV = afectación 11–16 (aquí 14), M = 18 e IGV informativo > 0. Sin IGV = afectación **inafecta 31–37** (aquí 32), M = 0 e IGV 0. La elección es tributaria: **decide Contabilidad**, no un formato. Esto reemplaza la pregunta 5 de §9.
3. El ejemplo "sin IGV" también deja `T1 = 0.00` y la línea con `J = 0.00`.
4. Todavía hay que probar en **nuestro** layout dónde va el IGV informativo (AO según Legacy; en el ejemplo 2021 es T) y la base de la línea. Para eso sirve el lazo de pruebas de §8: enviar y, si eFact rechaza, mandar CSV + error.
5. Fecha: los ejemplos usan `YYYY-MM-DD`; nosotros seguimos con `dd/mm/yyyy` porque es lo que procesa hoy. No cambiarla.

## 2c. Regla de oro: nuestra estructura, solo cambia lo gratuito

El CSV del módulo se genera **con la misma estructura que `ctrGenerarFEFacBolA` y que ya procesa eFact** (9 filas, `dd/mm/yyyy`, ítems desde fila 8, `FF00FF`). Los ejemplos de eFact (layout 2021) **no se copian**; solo se toman sus valores.

Un obsequio sale **igual que una venta normal nuestra** salvo estos campos (y nada más):

| Dónde | Venta normal (hoy) | Obsequio |
|---|---|---|
| FILA 1 · E, F, N, BH, BI, BJ | IGV / total / base | `0.00` |
| FILA 1 · V (gravadas) | base gravada | vacío |
| FILA 1 · **Y** | vacío | **total gratuito** |
| FILA 1 · **AO** | vacío | IGV informativo (solo afect. 11–16) |
| FILA 3 | guía / cuotas | vacía, sin cuotas |
| FILA 6 · A / **B** | monto en letras / vacío | "CERO Y 00/100 SOLES" / **leyenda 1002** |
| Línea · E, T | precio | `0.00` |
| Línea · **G, H** | vacíos | **valor referencial / `02`** |
| Línea · **K, L, M** | `10`, `1000`, `18` | **afectación, `9996`, 18 o 0** |
| Línea · I, J, AP | IGV | IGV informativo o `0.00` |
| Línea · U, X | precio × cant | valor referencial × cant |

Todo lo demás (emisor, receptor, orden de columnas, `FF00FF`, fechas) queda idéntico. Si algo falla, se corrige **un campo a la vez** contra el error de eFact (§8).

## 2d. Qué ejemplo seguimos y cuál no (y por qué)

| Ejemplo | Presentación | ¿Lo usamos? | Motivo |
|---|---|---|---|
| **Autonort** (boleta B025-0002035, casaca a colaboradora) | Op. gratuitas 38.59 · **IGV 6.95 informativo** · total 0.00 | **Sí, como modelo base** | Coincide con la lectura de SUNAT (Informe 096-2013): entrega a trabajadores de libre disposición = **retiro gravado** (cód. 11–16). Muestra el IGV que Vasco asume. Es la opción conservadora. |
| **eFact "Gratuita con igv"** (FD02-9030, afect. **14**) | M = 18, IGV informativo en líneas y cabecera | **Sí, como modelo de valores del CSV** | Es el equivalente en CSV de Autonort; confirma F=`01`, G/H, K/L/M, Y, leyenda y total 0.00 |
| **Virazon** (boleta EB01-720, canasta a trabajadores) | Op. gratuitas 67.80 · **IGV 0.00** · total 0.00 | **No, por ahora** | La foto **no muestra el código de afectación**: puede ser inafecto (3x) o gravado con IGV sin imprimir. Si fue inafecto, no hay sustento verificado (el Informe 096-2013 lo trata como retiro gravado) y el IGV quedaría sin declarar. Además es empresa agrícola, posible régimen distinto. |
| **eFact "Gratuita sin igv"** (FD01-000731, afect. **32**) | M = 0, IGV 0.00 | **Soportado, desactivado** | El sistema lo permite (selector), pero **solo se activa si Contabilidad confirma por escrito** el sustento del inafecto y el código exacto (31–36). |
| **Memory Kings** (B138-010246) | Boleta mixta gravado + gratuito | **No** | Es una bonificación dentro de una venta; fuera del alcance del módulo (§1). |

**Criterio:** ante la duda, **"con IGV"**. Si se emite con IGV algo que era inafecto se declara de más; si se emite sin IGV algo que era gravado queda IGV sin declarar. Cuando Contabilidad confirme el caso Virazon (§9), se habilita el modo "sin IGV".

## 3. Aislamiento respecto al flujo actual

| | Flujo actual | Módulo Comprobantes especiales |
|---|---|---|
| Origen | Pedido / guía / salida → `ventajf` | Formulario manual |
| Tablas | `ventajf`, `movimientosjf_2026`, `articulojf` | Tablas nuevas `comp_especialjf`, `comp_especial_detjf`, `comp_especial_seriejf` |
| Serie/correlativo | `talonariosjf` / argumentos | Serie propia (ver §9-1) |
| Generador CSV | `ctrGenerarFEFacBolA` | Función nueva, **no modifica** la anterior |
| Carpeta de salida | `DESTINO_INVOICE` / `DESTINO_BOLETA` | Las mismas (eFact ya las vigila) |
| Stock | Descuenta | **No** descuenta (ver §9-2) |
| Cobranza / CxC / Vasco Online | Sí | No ve estos documentos |
| Interruptor | — | Permiso de menú (como guías manuales) |

## 4. Modelo de datos (propuesto, siguiendo `gre_manualjf`)

**`comp_especialjf` (cabecera)**
`id`, `tipo_doc` (01/03), `serie`, `correlativo`, `fecha`, `moneda` (PEN), receptor (`tipo_doc_receptor`, `doc_receptor`, `nombre`, `direccion`, `ubigeo`, `email`), `categoria` (TRABAJADOR / CLIENTE / PROVEEDOR / OTRO), `afectacion` (11, 12, 13, 14, 16…), `lote`, `observacion`, `total_gratuito`, `igv_informativo`, `estado` (GENERADO / ENVIADO / ANULADO), `usuario`, `fecha_creacion`, `fecha_envio`, `csv_nombre`.

**`comp_especial_detjf` (líneas)**
`id`, `id_comprobante`, `item` (n.º), `unidad` (C62, etc.), `cantidad`, `descripcion` (≤500), `codigo` (≤30, opcional), `valor_ref_unit`, `valor_venta` (= ref × cant), `igv_informativo`.

**`comp_especial_seriejf`**: `tipo_doc`, `serie`, `ultimo_correlativo`, `activo`.

Las líneas **copian** datos de un artículo si se elige del buscador, pero **no lo enlazan** (sin tocar stock ni modelos).

## 5. Formulario (pantalla `comprobantes-especiales-crear`)

1. **Documento:** boleta o factura, serie, fecha.
2. **Receptor:** DNI/RUC (con buscador a clientes/proveedores/trabajadores si existe, o a mano).
3. **Motivo:** categoría + afectación (catálogo 07) + texto estándar de descripción.
4. **Ítems:** buscar artículo (copia) o escribir a mano: unidad, cantidad, descripción, valor referencial unitario.
5. **Resumen:** Op. Gratuitas, IGV informativo, total 0.00 (solo lectura).

Listado `comprobantes-especiales` con filtros por fecha, categoría, estado y receptor; totales de valor regalado (sirve de registro para Contabilidad).

## 5b. Emisión por lista (casos Vasco 2026)

Casos previstos: **panetones a clientes** (cód. 13/14) y **canastas de víveres a trabajadores** (cód. 16). Son bienes **comprados** (no están en `articulojf`) y cada receptor necesita **su propio comprobante**.

- Los ítems son **manuales** (descripción, unidad, valor referencial), sin enlazar artículos ni stock.
- **Emisión por lote:** un mismo ítem/valor/afectación + una **lista de receptores** (DNI o RUC y nombre, pegada o importada de Excel) → se crea **un comprobante GENERADO por persona**. Receptor con RUC → factura; con DNI → boleta (mismo lote).
- El lote se revisa en el listado y se **envía a EFACT por selección** (no todo de golpe), para probar primero con 1 comprobante.
- Cada lote guarda `lote` y `motivo`; el responsable es el usuario de sesión (`usuario_registro`), sin campo de "autoriza".

## 6. Generación del CSV (función nueva)

Misma estructura de 9 filas + `FF00FF` que ya procesa eFact. Valores propuestos (los marcados ⚠ se confirman con la primera prueba, ver §8):

**FILA 1 (cabecera)**

| Col. | Valor | Nota |
|------|-------|------|
| A | fecha `dd/mm/yyyy` | igual que hoy |
| B | `SERIE-CORRELATIVO` | |
| C | `01` o `03` | |
| D / G | `PEN` | |
| E / F | `0.00` | IGV cobrado = 0 ✔ (ejemplo eFact) |
| N | `0.00` | importe total ✔ (ejemplo eFact) |
| O | `CONTADO` (solo factura, como hoy) | sin cuotas |
| Q | `0101` | |
| V, X | vacío | sin gravado/exonerado |
| **Y** | **total gratuito** (Σ valor de venta) | columna vacía hoy; ✔ misma columna en el ejemplo |
| Z, AL | `0.00` ⚠ | subtotal / base IGV |
| **AO** | **IGV informativo** (Σ, solo afectación 11–16) | columna vacía hoy ⚠ |
| AS | n.º de líneas | |
| AT | `1` (como hoy) | |
| BH, BI, BJ | `0.00` | mismo bloque que usa nuestro CSV |

**FILA 3:** sin guía ni cuotas (`,,,,,` en boleta; en factura solo `ATTACH_DOC` si se mantiene).
**FILA 6:** A = `CERO Y 00/100 SOLES`; **B = `TRANSFERENCIA GRATUITA DE UN BIEN Y/O SERVICIO PRESTADO GRATUITAMENTE`** ✔ (texto del ejemplo eFact)
**FILA 7:** A = observaciones (motivo / "ENTREGA A TRABAJADORES"), resto vacío.

**FILA 8… (líneas)**

| Col. | Valor |
|------|-------|
| A–D | n.º, unidad (`C62` bienes / `ZZ` servicios), cantidad, descripción |
| E | `0.00` |
| F | `01` ✔ (confirmado por el ejemplo eFact) |
| **G / H** | **valor referencial unitario / `02`** |
| I, J | IGV informativo (afect. 11–16) o `0.00` (31–37) ⚠ |
| **K** | afectación elegida (14, 16, 32…; ver §2b) |
| **L** | `9996` |
| M | `18` (afect. 11–16) / `0` (afect. 31–37) |
| S | código |
| T | `0.00` |
| **U** | valor ref. × cantidad |
| W | = U ⚠ (hoy se deja vacía) |
| X | = U |
| AP | `0.00` |

## 7. Impresión

Formato **nuevo multipágina estilo SAP** de facturas y boletas (`impresion_bolfact_sap.php`; el PDF TCPDF `reporte_factura.php` es el formato antiguo y **no** se usa). Mismo HTML, mismo CSS (`css/ticket_v9.css`) y misma paginación por líneas: cabecera completa (logos, datos de la empresa, recuadro RUC/tipo/número y "Hoja X de Y") en la hoja 1, cabecera compacta desde la hoja 2, "Continúa en la siguiente hoja", y el pie siempre junto a los últimos ítems.

Archivos: `vistas/reportes_ticket/impresion_comprobante_especial_html.php` (función pura `ceImpresionHtml($g)`) y `impresion_comprobante_especial.php?id=` (permiso + salida). Se abre con el icono de impresora del listado y se imprime desde el navegador, como las demás.

Lo que cambia respecto a una venta normal:
- Título **BOLETA / FACTURA DE VENTA ELECTRONICA** (igual que hoy), receptor con DNI o RUC.
- Cabecera: *Motivo* y *Usuario* en lugar de código de cliente y vendedor; *Afectación IGV* (código y nombre oficial del Catálogo 07, p. ej. `16 · Gravado – Retiro por entrega a trabajadores`) en lugar de la guía de remisión. El listado también muestra código y nombre.
- Columnas **V.REF.** y **VALOR** (valor referencial unitario y valor gratuito de la línea).
- Totales: Op. Gravadas / Inafecta / Exonerada en 0.00, **Total Op. Gratuitas** = valor del regalo, **IGV 18 % informativo** (sin el 18 % y en 0.00 si la afectación no es 11–16), **TOTAL 0.00**.
- **Son: CERO Y 00/100 SOLES** y la leyenda **TRANSFERENCIA GRATUITA DE UN BIEN Y/O SERVICIO PRESTADO GRATUITAMENTE**.
- Se quita lo que no aplica a un obsequio: cuentas autorizadas, Yape, aviso importante y "CANCELADO".
- Pie: la nota habitual y **"Vista previa interna del sistema: el PDF oficial (con código QR y hash) lo emite eFact"**.

Usa las constantes `BOLFACT_SAP_LINEAS_*` de `config.php` si existen (misma capacidad de hoja que las facturas). Verificado con PHP 5.6 (el del `Dockerfile`) y Chrome: boleta de 2 ítems en 1 hoja y factura de 40 ítems en 2 hojas.

## 8. Flujo y lazo de pruebas con eFact

**Estados:** guardar (GENERADO, editable) → "Enviar a EFACT" genera el CSV en la carpeta vigilada → ENVIADO (no editable). Anular solo en GENERADO.

**Cómo avanzar con eFact (acordado):**
1. Emitir una boleta de prueba (a nombre de Joel) con los valores de §6 y enviarla.
2. Si eFact la rechaza o la procesa mal, guardar el **CSV exacto + el mensaje de error** y enviarlos a eFact preguntando qué columna corregir. Con eso se resuelven los ⚠ de §6 sin adivinar.
3. Repetir con una factura de prueba (RUC) y luego dar de baja los comprobantes de prueba.
4. Registrar cada intento en `docs/efact/pruebas-comprobantes-especiales/REGISTRO.md` (plantilla abajo) para tener historial.

| N.º | Fecha | Documento | CSV (archivo) | Respuesta de eFact / error | Cambio aplicado |
|----|-------|-----------|---------------|----------------------------|-----------------|
| 1 | | | | | |

## 9. Decisiones abiertas

1. **Serie/correlativo:** ¿serie propia (p. ej. `BG01` / `FG01`, que no choque con `B001`, `B002`, `BR01`, `FR01`, `FJ01`…) o misma serie que las ventas? Recomendación: **serie propia** para no tocar `talonariosjf`; confirmar con Contabilidad que es válido y con eFact que la acepta/hay que registrarla.
2. **Stock:** ¿los regalos salen del inventario? Si sí, definir salida aparte (no desde este módulo).
3. **Afectación (con IGV / sin IGV):** el módulo soporta **las dos** con un selector (11–16 = con IGV, 31–37 = sin IGV; el resto del CSV sale solo, §6). **Recomendación: por defecto "con IGV"** (retiro gravado): trabajadores **16**, obsequio a clientes/proveedores **13** (o **14** si es publicidad), premios **11**, donaciones **12**. "Sin IGV" solo con sustento que dé el contador. El porqué, con base legal de SUNAT, está en **`GRATUITAS-codigos-afectacion-sustento.md` (resumen para Contabilidad; detalle en `-detalle.md`)** (documento para Contabilidad). Esto no es asesoría tributaria.
4. **Factura:** pedir a Contabilidad un ejemplo (proveedor o cliente con RUC).
5. **Con IGV o sin IGV:** no es formato, es la afectación (11–16 gravado-retiro vs 31–37 inafecto-retiro). Contabilidad define cuál aplica a cada tipo de regalo; la impresión sigue a esa decisión (ver §2b).
6. **Descripción estándar** de las líneas (cumpleaños, campaña, navidad…) y datos mínimos del receptor.
7. **Corregir** `convertir(0)` → "CERO Y 00/100 SOLES" (archivo `extensiones/cantidad_en_letras_v2.php`, se usa también en el flujo normal: validar que no cambie otros casos).
8. **Mixto (bonificación 15):** ¿se necesita? Si sí, fase 2 en el flujo normal.

## 10. Archivos a crear (cuando se apruebe)

- `docs/sql/comprobantes-especiales.sql`
- `modelos/comprobantes-especiales.modelo.php`, `controladores/comprobantes-especiales.controlador.php`, `ajax/comprobantes-especiales.ajax.php`
- `vistas/modulos/facturacion/comprobantes-especiales.php` (listado) y `comprobantes-especiales-crear.php` (formulario)
- `vistas/js/comprobantes-especiales.js`, `vistas/reportes_ticket/impresion_comprobante_especial_html.php` y `impresion_comprobante_especial.php`
- Cableado: `index.php`, `vistas/plantilla.php`, `vistas/modulos/menu.php`

## 11. Estándar de comprobantes especiales (decisión y cambio del 9-nov-2026)

Se acordó **no hacer un módulo por cada tipo de comprobante** ni llamarlo "Obsequios". Existe **un solo módulo, "Comprobantes especiales"** (menú, ruta `comprobantes-especiales`), aislado del flujo de ventas, donde **se elige el tipo**. Hoy solo existe el tipo **GRATUITA** (operaciones gratuitas / obsequios).

**Implementado:**
- Menú **Comprobantes especiales → Nuevo comprobante / Listado y envío**. El formulario lleva a la derecha una **guía rápida** (columna 3/12) con los pasos y la tabla de afectación según el tipo elegido; el texto vive en el perfil del tipo (`ayuda` en `perfiles()`). El formulario empieza con el selector **"Tipo de comprobante especial"** (hoy: *Operación gratuita*); el listado tiene columna y filtro por tipo.
- Tablas genéricas `comp_especialjf`, `comp_especial_detjf`, `comp_especial_seriejf`; columna `tipo_operacion` (default `GRATUITA`).
- Registro de tipos en `ControladorComprobantesEspeciales::perfiles()`: cada tipo declara nombre, categorías, afectaciones y sugerencias.
- `armarLineas()` **despacha por tipo** a `lineas<Tipo>()` (hoy `lineasGratuita`). La estructura base del CSV (9 filas, `FF00FF`, columnas) es común; cada tipo declara solo lo que cambia (como la tabla de §2c). Un tipo sin generador falla con mensaje claro.
- Verificado: el CSV de la gratuita no cambió con la reestructuración (solo la etiqueta de FILA 7 columna E: "GRATUITA <categoría>").
- Instalación nueva: `docs/sql/comprobantes-especiales.sql`. Si ya se había ejecutado la versión anterior (tablas `obsequio*`): `docs/sql/comprobantes-especiales-migracion.sql` (una sola vez; conserva datos).
- Constantes opcionales en `config.php`: `COMPROBANTES_ESPECIALES_USUARIOS` (ids con acceso; por defecto 70 y 6) y `GRATUITAS_PERMITE_INAFECTO` (sin IGV; desactivado).

**Para agregar un tipo nuevo (cuando lo pidan):**
1. Elegirlo y **estudiar su estructura** como se hizo con la gratuita: columnas contra el manual de eFact, ejemplo de eFact, preguntas a Contabilidad.
2. Agregar su perfil en `perfiles()` y su método `lineas<Tipo>()`.
3. Si necesita campos propios (anticipo, exportación…), tabla complementaria; la parte de ítems/montos de `ctrNormalizarLote()` hoy es la de la gratuita y se separa por perfil.
4. Probar con baja inmediata y registrar en `pruebas-comprobantes-especiales/REGISTRO.md`.
