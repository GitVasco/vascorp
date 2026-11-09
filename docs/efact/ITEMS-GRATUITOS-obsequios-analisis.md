# Ítems gratuitos (obsequios) en factura / boleta electrónica — análisis de brechas

**Emisor:** Corporación Vasco S.A.C. — RUC 20513613939
**Fecha del análisis:** 9 de noviembre de 2026
**Estado:** solo análisis y registro. **No se ha modificado código.**
**Siguiente paso:** la propuesta de implementación como módulo aislado está en `COMPROBANTES-ESPECIALES-diseno.md` (reemplaza las cambios C1–C9 de §5 para el alcance acordado).
**Motivo:** este año se darán regalos a proveedores, clientes y trabajadores y debe quedar documentado con comprobante electrónico.

**Fuentes revisadas**
- Manual `efact/docs/Legacy Factura CSV 2.1.pdf` (referencia principal; también se contrastó con `Factura - CSV 2.1.pdf`).
- `docs/efact/INCONSISTENCIAS-CSV-vs-documentacion.md` y `ROLLBACK-fe-factura-boleta.md` (contexto previo).
- Código: `controladores/facturacion.controlador.php` (`ctrGenerarFEFacBolA`), `modelos/facturacion.modelo.php` (`mdlFEFacturaCabA`, `mdlFEFacturaDetA`), `tablas/ventajf.sql`, PDFs/tickets, CSV reales en `efact/`.

---

## 1. Conclusión corta

Hoy el sistema **solo sabe emitir operaciones gravadas (afectación 10, tributo 1000)** o exportación (40/9995). No existe ningún concepto de "ítem gratuito" en la base de datos, en el armado del CSV, ni en las impresiones. Emitir un obsequio con lo que hay produciría **un comprobante gravado con IGV cobrado** (incorrecto) o un **rechazo del OSE** si se pone precio 0.

Para soportarlo hay que tocar **cuatro capas**: datos (marcar la línea), detalle del CSV, cabecera del CSV (+ leyenda) e impresiones/reportes. Ninguna requiere mover columnas de lugar: **todas las columnas necesarias ya existen en nuestro layout actual y están vacías**, lo que reduce el riesgo de romper el procesamiento (ver §4).

---

## 2. Qué dice el manual Legacy sobre una operación gratuita

### 2.1 Por línea (FILA 8)

| Col. | Campo | Regla para gratuito |
|------|-------|---------------------|
| E | Precio venta unitario | **0.00** |
| F | Código precio unitario | `01` (el manual lo fija en 01) |
| **G** | **Valor referencial unitario** | **Obligatorio** (precio de referencia del bien) |
| **H** | **Código valor referencial** | **`02`** |
| I y J | Monto total IGV | Para afectación 11–16: `base × 18%` (IGV **informativo**, no se cobra). Para 21, 31–37: `0.00` |
| **K** | Afectación IGV (cat. 07) | **11 a 16, 21, 31 a 37** (ver §3) |
| **L** | Código de tributo | **`9996`** (Gratuito) |
| M | Porcentaje IGV | 11–16: `18`. Resto: `0` |
| T | Valor unitario | **0.00** |
| **U** | Valor de venta | **valor referencial × cantidad** |
| W | Total de la línea | Valor de venta + impuestos |
| X | Monto base IGV | = valor de venta (U) |
| AP | Monto total impuestos | Solo suma líneas con tributo 1000/1016 → **0.00** en gratuitas |

### 2.2 Cabecera (FILA 1) y leyenda (FILA 6)

| Ubicación | Campo | Regla |
|-----------|-------|-------|
| 1-**Y** | Total operaciones gratuitas | Suma de "Valor de venta" de líneas con tributo 9996 |
| 1-**AO** | Sumatoria impuestos de operaciones gratuitas | Suma del IGV de líneas 9996 con afectación 11–16 |
| 1-N | Importe total | No incluye lo gratuito. Puede ser 0.00 si el comprobante es solo de obsequios |
| 6-**B** | Leyenda 1002 | **"TRANSFERENCIA GRATUITA DE UN BIEN Y/O SERVICIO PRESTADO GRATUITAMENTE"** |

El manual "Factura - CSV 2.1" (2021) contiene las mismas reglas (columna Y, AO equivalente, leyenda 1002 en 6-B, 9996), por lo que el contenido conceptual coincide entre ambos manuales; solo cambian las posiciones en las zonas ya identificadas como inconsistentes.

---

## 3. Catálogo 07 — qué código usar según el destinatario

| Cód. | Descripción (Catálogo 07) | Uso probable en Vasco |
|------|---------------------------|----------------------|
| 11 | Gravado – Retiro por premio | Premio / sorteo a clientes |
| 12 | Gravado – Retiro por donación | Donaciones reales (ver `GRATUITAS-codigos-afectacion-sustento.md`) |
| **13** | **Gravado – Retiro** | **Obsequio a clientes y proveedores** |
| 14 | Gravado – Retiro por publicidad | Material/producto promocional |
| 15 | Gravado – Bonificaciones | Bonificación ligada a una venta (ej. "compra 10, lleva 1") |
| **16** | **Gravado – Retiro por entrega a trabajadores** | **Regalo a trabajadores** |
| 21 | Exonerado – Transferencia gratuita | Solo si el bien es exonerado (no aplica a prendas) |
| 31–37 | Inafecto – retiros (bonificación, muestras, convenio, premio, publicidad…) | Solo si el bien es inafecto |

> **Actualizado:** el sustento legal y la propuesta corregida por destinatario están en `GRATUITAS-codigos-afectacion-sustento.md`.
>
> **Pendiente de Contabilidad:** confirmar qué código aplica a cada tipo de destinatario y cómo se trata el IGV del retiro y el gasto (límites de deducibilidad de atenciones, si lo de trabajadores va por planilla/remuneración, etc.). Este documento solo cubre la parte técnica del comprobante, no el criterio tributario.

---

## 4. Brechas encontradas en el sistema

### B1. No hay forma de marcar una línea como gratuita (datos)
- `tablas/ventajf.sql` (dump antiguo; verificar contra BD real) no tiene columna de afectación ni de monto gratuito; solo `neto`, `igv`, `dscto`, `total`.
- `movimientosjf_2026` solo trae `precio` y `cantidad`; no hay "tipo de afectación" ni "valor referencial".
- Usar `precio = 0` como señal es **frágil**: hoy un precio 0 en una línea gravada sale con tributo 1000 y valor 0 y el OSE lo rechaza; además no distingue el tipo de regalo (12 vs 16).
- Al facturar (`ctrFacturarGuia`, `ctrFacturarSalida`, `ctrFacturarB`) los totales se calculan como `cantidad × precio` y se guardan en `ventajf` sin concepto de gratuito.

**Cambio necesario:** definir dónde se registra el motivo (afectación por línea, p. ej. columna en `movimientosjf_2026`, o a nivel documento en `ventajf`) y el valor referencial. Decisión de diseño pendiente (ver §7).

### B2. Detalle del CSV: todo está fijo en "gravado" — `mdlFEFacturaDetA`
Valores hardcodeados hoy:

| Campo | Hoy | Gratuito requiere |
|-------|-----|-------------------|
| E (`e9`) | `precio × 1.18` | `0.00` |
| G / H | vacíos (`,,,`) | valor referencial / `02` |
| I, J | `base × 0.18` | 11–16: IGV informativo; otros: 0 |
| K (`k9`) | `'10'` | 11–16 / 21 / 31–37 |
| L (`l9`) | `'1000'` | `'9996'` |
| M (`m9`) | `'18'` | 18 o 0 según afectación |
| T (`t9`) | `precio` | `0.00` |
| U (`u9`) / X (`x9`) | `precio × cant` | `valor referencial × cant` |
| AP (`ap9`) | IGV de la línea | `0.00` |

Además la consulta **agrupa por `a.modelo`** (suma cantidades del mismo modelo): una línea gratuita y una cobrada del mismo modelo se **fusionarían** y perderían el tratamiento. Hay que agrupar también por afectación.

También: la variante de exportación (`mdlFEFacturaDetAExportacion`) y las notas de crédito/débito (`mdlFENCDetA/B`, `mdlFENDDetA`) tienen sus propios mapeos fijos; una NC sobre un comprobante con gratuitos (anulación/devolución) también necesitaría el tratamiento.

### B3. Cabecera del CSV: no se emiten Y ni AO — `ctrGenerarFEFacBolA` / `mdlFEFacturaCabA`
- Posiciones verificadas en el armado de FILA 1: **Y (col 25)** está entre `v1` (col 22) y `z1` (col 26), y **AO (col 41)** entre `al1` (col 38) y `as1` (col 45). Ambas hoy salen **vacías** → hay lugar sin mover nada.
- Todos los totales salen de `ventajf` (`neto - dscto`, `igv`, `total`); no existe `SUM` de gratuitos.
- Un comprobante **solo de obsequios** tendría `total = 0`: validar con eFact que acepta importe total 0.00 y qué deben llevar V/Z/AL/BH/BI/BJ en ese caso.
- Si el comprobante es a crédito, FILA 3 usa `v.total` como cuota (`f3`) → con total 0 saldría `CUOTA001` con monto 0. Los obsequios deben ir como **CONTADO sin cuotas**.
- Los campos E/F (monto IGV) y BH hoy llevan el IGV; con gratuitos puros deben quedar en 0 (el IGV informativo va en AO, no en el total).

### B4. Leyenda 1002 ausente (FILA 6)
- FILA 6 solo lleva A (monto en letras); B–… vacías. La leyenda "TRANSFERENCIA GRATUITA…" es obligatoria cuando hay operaciones gratuitas (6-B).
- **Bug latente adicional:** `convertir(0)` devuelve `" Y 00/100 SOLES"` (sin "CERO"). Un comprobante total 0 saldría con el monto en letras mal formado. Corregir en `extensiones/cantidad_en_letras_v2.php`.

### B5. Impresiones y representación
- `reporte_factura.php`, `reporte_boleta.php`, `impresion_bolfact.php`, `impresion_bolfact_sap.php` ya tienen la etiqueta **"Total Op. Gratuitas"** pero con **`'0.00'` fijo** (no calculada).
- Las líneas del detalle impreso no distinguen gratuitas (deberían mostrar precio 0.00 / "GRATUITO" y el valor referencial).
- El PDF/ticket debe mostrar la leyenda 1002.
- Revisar `ctrCrearFacturaXML` (≈ línea 1124) y similares: tienen `TaxExemptionReasonCode` **10 fijo**. Confirmar si siguen en uso; si el flujo vigente es solo CSV → eFact, no aplica.

### B6. Reportes, cobranza e integraciones (efectos colaterales)
- Reportes de ventas (`ctrMostrarVentaResumen/Detalle`, dashboards, `reportes_excel/fe_factura.php`): un total 0 no suma, pero el **valor referencial** del regalo no aparecería en ningún lado y habría que decidir si se reporta aparte.
- Cuentas corrientes / dashboard CxC / sync Vasco Online: un documento con total 0 no debe generar saldo, letras ni cuotas.
- Stock/kardex: el obsequio **sí debe descontar stock** (revisar que el flujo de salida no dependa de `precio > 0`).

### B7. Riesgo ya conocido del layout
El historial (`INCONSISTENCIAS…`, `ROLLBACK…`) muestra que mover columnas rompió la emisión. Para gratuitos **no hace falta mover nada**, pero **sí se debe validar con eFact** que, en nuestro layout actual, leen: Y, AO, 6-B, G/H de línea y K/L = 11–16 / 9996. Esa validación es la parte que no se puede inferir del PDF.

---

## 5. Cambios propuestos (para cuando se decida implementar)

| # | Capa | Cambio | Archivos |
|---|------|--------|----------|
| C1 | Datos | Registrar afectación (11–16, etc.) y valor referencial por línea; flag a nivel documento (`tiene_gratuitos`) | script SQL nuevo en `docs/sql/`, `movimientosjf_2026`/`ventajf` |
| C2 | Captura | Pantalla/flujo para marcar "obsequio" al facturar (motivo + destinatario) | `facturacion.js`, vistas de facturación/pedidos, `ctrFacturarGuia/Salida/B` |
| C3 | Detalle CSV | Rama gratuita en `mdlFEFacturaDetA` (K/L/M/E/G/H/I/J/T/U/X/AP) y `GROUP BY` con afectación | `facturacion.modelo.php` |
| C4 | Cabecera CSV | Calcular y emitir Y y AO; total 0 / CONTADO / sin cuotas en solo-obsequios | `mdlFEFacturaCabA`, `ctrGenerarFEFacBolA` |
| C5 | Leyenda | FILA 6 col B con 1002 cuando haya gratuitas; corregir "CERO" en `convertir(0)` | `facturacion.controlador.php`, `cantidad_en_letras_v2.php` |
| C6 | Impresión | Calcular "Total Op. Gratuitas", marcar líneas gratuitas, mostrar leyenda | `reporte_factura.php`, `reporte_boleta.php`, `impresion_bolfact*.php` |
| C7 | NC/ND | Soportar gratuitos en notas de crédito/débito si se necesita anular/corregir | `mdlFENC*`, `mdlFEND*` |
| C8 | Reportes | Decidir cómo se reportan (ventas / registro de obsequios) y excluir de cobranza | reportes, CxC |
| C9 | Seguridad de despliegue | Kill-switch en `config.php` (como `FE_CSV_RETENCION`) p. ej. `FE_CSV_GRATUITOS=false` hasta validar | `controladores/config.php` |

---

## 6. Plan de validación sugerido

1. Preguntar a eFact (anexar a la solicitud de matriz ya planeada en `INCONSISTENCIAS…` §5): ¿lee **Y, AO, 6-B, G, H** en nuestro layout? ¿acepta comprobante con **total 0.00**? ¿la col. **F** de línea va `01` o `02` en gratuitos? ¿col. **W** (total línea, hoy vacía) es obligatoria aquí?
2. Aplicar el mismo método ya usado: **un comprobante de prueba a nombre de Joel**, mixto (1 línea gravada + 1 gratuita) y otro solo-obsequio, y luego **dar de baja**.
3. Probar factura (cliente/proveedor con RUC) y boleta (trabajador con DNI), moneda PEN.
4. Verificar PDF (leyenda, total en letras, "Total Op. Gratuitas").
5. Solo con el flag activado en pruebas; producción sigue igual mientras `FE_CSV_GRATUITOS=false`.

---

## 7. Decisiones abiertas

1. **Contabilidad:** código de afectación por destinatario (§3), base del IGV (valor de venta/mercado vs. costo), y si trabajadores se regala vía boleta o por planilla.
2. **Diseño de datos:** ¿afectación por línea o por documento? Recomendación: **por documento** al inicio (un comprobante = un tipo de regalo/destinatario; más simple, evita mezclar), con soporte mixto gravado+gratuito en una segunda etapa.
3. **Valor referencial:** ¿precio de lista vigente del artículo? ¿costo? Definir fuente.
4. **Cliente receptor:** documentos requeridos para boleta a trabajadores/clientes sin RUC (DNI) y tope de importe.
5. **Registro de obsequios:** si Contabilidad quiere un reporte separado (quién recibió qué, motivo, valor) además del comprobante.
