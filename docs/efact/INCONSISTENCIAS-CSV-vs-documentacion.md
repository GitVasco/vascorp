# Informe de inconsistencias — CSV factura/boleta electrónica

**Emisor:** Corporación Vasco S.A.C. — RUC 20513613939  
**Destinatario:** eFact (proveedor OSE)  
**Fecha:** 30 de julio de 2026

---

## 1. Objetivo

Documentar las diferencias entre:

- Los **archivos CSV que enviamos hoy** y que **procesan correctamente** en su OSE.
- El manual **Legacy Factura CSV 2.1** (el que más se aproxima a nuestro formato).
- El manual **Factura - CSV 2.1** (2021) que nos indicaron vía contabilidad.

**Premisa:** El layout de nuestros CSV vigentes es la **referencia operativa válida**. Cualquier cambio de ubicación de campos según los PDF, sin una matriz oficial alineada a ese layout, **rompe el procesamiento**. No es que falte capacidad técnica: tenemos implementación de guías, orden de compra y otros campos, pero **no podemos ubicarlos donde dicen los manuales** sin confirmación de eFact sobre qué columnas lee realmente su servicio para nuestro RUC.

**Ejemplo adjunto recomendado:** `20513613939-01-FR01-00002243.csv` (factura crédito con guía de remisión).

---

## 2. Estructura general del archivo (resumen)

| Aspecto | Legacy CSV 2.1 | Factura - CSV 2.1 | **Nuestros CSV (procesan OK)** |
|--------|----------------|-------------------|--------------------------------|
| Filas de cabecera antes de ítems | 9 | **10** | **9** |
| Formato de fecha en cabecera | dd/mm/yyyy | YYYY-MM-DD | **dd/mm/yyyy** |
| Guías / cuotas relacionadas | FILA 3 | **FILA 4** | **FILA 3** |
| Orden de compra | FILA 7 col. B | **FILA 8 col. B** | **FILA 7 col. B** |
| Emisor / receptor / letras | Filas 4 / 5 / 6 | Filas 5 / 6 / 7 | **Filas 4 / 5 / 6** |
| Ítems | FILA 8 en adelante | FILA 9 en adelante | **FILA 8 en adelante** |
| Cierre de ítems | (no detallado) | (no detallado) | **`FF00FF` en última línea** |

---

## 3. FILA 1 — Análisis columna por columna

Referencia: factura **FR01-00002243** (PEN, crédito, 3 ítems, guía TR01-00001844).

Valores reales enviados:

| Col. | Valor enviado |
|------|----------------|
| A | 24/07/2026 |
| B | FR01-00002243 |
| C | 01 |
| D | PEN |
| E | 229.61 |
| F | 229.61 |
| G | PEN |
| H – M | *(vacío)* |
| N | 1505.21 |
| O | CREDITO |
| P | *(vacío)* |
| Q | 0101 |
| R – U | *(vacío)* |
| V | 1275.60 |
| W – Y | *(vacío)* |
| Z | 1275.60 |
| AA – AK | *(vacío)* |
| AL | 1275.60 |
| AM – AR | *(vacío)* |
| AS | 3 |
| AT | 1 |
| AU – BG | *(vacío)* |
| BH | 229.61 |
| BI | 1275.60 |
| BJ | 1505.21 |
| BK – BM | *(vacío)* |
| BN | *(vacío)* |
| BO – BR | *(vacío salvo retención: ver §3.8)* |

Total: **83 columnas** en FILA 1.

---

### 3.1 Columnas A – G (identificación y moneda)

| Col. | Legacy CSV 2.1 indica | Factura - CSV 2.1 indica | **Lo que enviamos** | ¿Cuadra Legacy? | ¿Cuadra PDF 2021? |
|------|----------------------|--------------------------|---------------------|-----------------|-------------------|
| A | Fecha dd/mm/yyyy | Fecha **YYYY-MM-DD** | 24/07/2026 | ✓ | ✗ |
| B | Serie-correlativo | Serie-correlativo | FR01-00002243 | ✓ | ✓ |
| C | Tipo documento 01 | Tipo documento 01 | 01 | ✓ | ✓ |
| D | Tipo moneda | Tipo moneda | PEN | ✓ | ✓ |
| E | Total IGV o IVAP | **Sumatoria monto base IGV** | 229.61 (IGV) | Parcial* | ✗ |
| F | Total IGV o IVAP | Total IGV o IVAP | 229.61 | ✓ | ✓ |
| G | Tipo moneda IGV | Tipo moneda IGV | PEN | ✓ | ✓ |

\* Legacy también denomina E como “Total IGV”; el PDF 2021 exige **base imponible** en E. Nosotros enviamos el **monto de IGV** en E y F. **Este es el formato que procesa correctamente hoy.**

---

### 3.2 Columnas H – M (ISC y otros tributos)

| Col. | Legacy / PDF 2021 | **Lo que enviamos** |
|------|-------------------|---------------------|
| H – M | ISC, otros tributos (opcional) | **Vacío** (operaciones sin ISC) |

Sin observación: coherente en ambos manuales cuando no hay ISC.

---

### 3.3 Columnas N – Q (total, forma de pago, operación) — **desfase crítico**

| Col. | Legacy CSV 2.1 indica | Factura - CSV 2.1 indica | **Lo que enviamos** | ¿Cuadra Legacy? | ¿Cuadra PDF 2021? |
|------|----------------------|--------------------------|---------------------|-----------------|-------------------|
| N | Importe total comprobante | Importe total comprobante | 1505.21 | ✓ | ✓ |
| O | **Descuentos globales** | **Forma de pago** (Contado/Credito) | **CREDITO** | **✗** | ✓ |
| P | Sumatoria otros cargos | Monto neto pendiente (si crédito) | *(vacío)* | ✓ vacío | Parcial |
| Q | Tipo operación (0101…) | Tipo operación | 0101 | ✓ | ✓ |

**Inconsistencia principal con Legacy:**  
- En Legacy, **forma de pago** está en columna **BN** (aprox. posición 66), no en O.  
- Nosotros enviamos **CREDITO en columna O** y dejamos **BN vacía**.  
- Si aplicamos Legacy al pie de la letra, CREDITO estaría mal ubicado (O sería descuento global) y BN debería llevar Contado/Credito. **Ese layout no es el que procesa nuestro OSE.**

**Inconsistencia entre manuales:**  
- Legacy y PDF 2021 **no coinciden** en la columna de forma de pago (O vs BN).  
- Nuestro CSV coincide con la **posición O del PDF 2021**, no con **BN del Legacy**.

---

### 3.4 Columnas R – U (anticipos)

| Col. | Legacy indica | **Lo que enviamos** |
|------|---------------|---------------------|
| R – U | Moneda, monto, tipo y serie de anticipos | **Vacío** |

Correcto cuando no hay anticipos. El PDF 2021 reordena estas columnas respecto al Legacy.

---

### 3.5 Columnas V – AL (totales por tipo de operación)

| Col. | Legacy indica | **Lo que enviamos** | ¿Cuadra Legacy? |
|------|---------------|---------------------|-----------------|
| V | Total operaciones gravadas | 1275.60 | ✓ |
| W | Total operaciones inafectas | *(vacío)* | ✓ |
| X | Total operaciones exoneradas | *(vacío)* | ✓ |
| Y | Total operaciones gratuitas | *(vacío)* | ✓ |
| Z | Subtotal valor venta | 1275.60 | ✓ |
| AA – AK | Percepción, detracción, ICBPER, etc. | *(vacío)* | ✓ |
| AL | Sumatoria monto **base** IGV o IVAP | 1275.60 | ✓ |

En este tramo, **nuestros valores son coherentes con Legacy** para una venta gravada simple.

En el **PDF 2021**, varias de estas columnas están **desplazadas** (por ejemplo, forma de pago en O desplaza todo el bloque intermedio).

---

### 3.6 Columnas AS – AT (contadores)

| Col. | Legacy indica | **Lo que enviamos** | ¿Cuadra Legacy? |
|------|---------------|---------------------|-----------------|
| AS | Cantidad de líneas del documento | 3 | ✓ |
| AT | Cantidad de filas en FILA 3 (guías/cuotas) | 1 | ✓ |

El PDF 2021 ubica el contador de guías en **columna AN** de FILA 1, no en AT.

---

### 3.7 Columnas BH – BJ — **mismo nombre de columna, distinto significado según manual**

| Col. | Legacy CSV 2.1 indica | Factura - CSV 2.1 indica | **Lo que enviamos** |
|------|----------------------|--------------------------|---------------------|
| **BB** | *(no usamos)* | **Monto total de impuestos** | *(vacío)* |
| **BC** | *(no usamos)* | **Total valor de venta** | *(vacío)* |
| **BD** | *(no usamos)* | **Total precio de venta** | *(vacío)* |
| **BH** | **Monto total de impuestos** (IGV + ISC + ICBPER + otros) | **Total descuentos AB** | **229.61** (IGV) |
| **BI** | **Total valor de venta** | **Monto total Anticipo ISC que AB** | **1275.60** (base gravada) |
| **BJ** | **Total precio de venta** | **Monto base retención renta 2da categoría** | **1505.21** (importe total) |

**Este es uno de los puntos más críticos:**

1. **Legacy** define BH / BI / BJ como totales de impuestos, valor de venta y precio de venta. Nosotros enviamos ahí **229.61 / 1275.60 / 1505.21**, que cuadran aritméticamente (base + IGV = total) y **procesan bien**.

2. **Factura - CSV 2.1** asigna a las mismas letras **BH, BI, BJ** campos **completamente distintos** (descuentos AB, anticipo ISC, retención renta). Si siguiéramos ese PDF, **los mismos números irían a conceptos incorrectos** y el XML fallaría.

3. En el PDF 2021, los totales equivalentes a nuestros BH/BI/BJ estarían en **BB, BC y BD** (columnas que nosotros dejamos vacías). Es decir: **tres manuales, tres mapas distintos** para los totales de cabecera.

**Conclusión FILA 1:** Ni Legacy ni PDF 2021 describen por completo el mapa real de nuestra FILA 1. El PDF 2021 **contradice** Legacy en forma de pago (O vs BN) y en el significado de BH–BJ vs BB–BD.

---

### 3.8 Columnas BN – BR (forma de pago Legacy y retención IGV)

| Col. | Legacy indica | Factura - CSV 2.1 indica | **Lo que enviamos** |
|------|---------------|--------------------------|---------------------|
| BN | **Forma de pago** (Contado/Credito) | *(no existe; forma de pago en O)* | **Vacío** |
| BO | Monto neto pendiente de pago | *(incluido en P en PDF 2021)* | **Vacío** |
| BP | Monto base retención IGV | *(columna AI en PDF 2021)* | **Si `FE_CSV_RETENCION` y cliente agente:** = total (BJ); si no, vacío |
| BQ | Factor retención IGV | *(columna AJ en PDF 2021)* | **Si aplica:** `0.03` (`FE_CSV_RETENCION_FACTOR`) |
| BR | Monto total retención IGV | *(columna AK en PDF 2021)* | **Si aplica:** base × factor |

**Retención IGV (implementada Legacy BP–BR):** kill switch `FE_CSV_RETENCION` en `controladores/config.php` (default `false` hasta validar eFact). Condiciones: factura `01`, no exportación, `clientesjf.agente_retencion = 1`. Ver `docs/efact/ROLLBACK-fe-factura-boleta.md`.

**Forma de pago:** Legacy exige **BN**; nosotros la enviamos en **O** (como PDF 2021). **BN vacía es correcta para nuestro procesamiento actual**, incorrecta según Legacy puro.

---

## 4. Otras filas (resumen)

### FILA 2
- Legacy / PDF 2021: datos de traslado (guía remitente en factura).  
- **Nosotros: fila vacía.** Procesamiento OK sin esta fila.

### FILA 3 — Guías y cuotas
- **Legacy:** guía (A–B) + cuotas (E–G) + `ATTACH_DOC` (H).  
- **PDF 2021:** misma información en **FILA 4** (FILA 3 reservada a anticipos con `PREPAID_DOC`).  
- **Nosotros:** `TR01-00001844,09,,,CUOTA001,1505.21,07/09/2026,ATTACH_DOC` en **FILA 3**.  
- **Riesgo:** indicaciones del PDF 2021 (“poner guía en FILA 4”) **rompen** el layout que hoy acepta el OSE.

### FILA 7 — Orden de compra y adicionales
- **Legacy:** col. B = orden de compra; col. C = fecha vencimiento.  
- **PDF 2021:** orden de compra en **FILA 8 col. B**.  
- **Nosotros:** col. B = orden de compra; col. C = fecha de cuota; cols. D–G = datos comerciales de impresión (cliente, condición, neto, vendedor).  
- Es un uso **extendido** de FILA 7 acordado con la operación histórica; mover OC a FILA 8 según PDF 2021 **no es directo** sin redefinir el resto de columnas.

---

## 5. Solicitud formal a eFact

1. **Confirmar el layout CSV** que procesa el servicio contratado para RUC 20513613939: ¿9 o 10 filas de cabecera? ¿Mapa Legacy, PDF 2021 u otro?

2. **Entregar matriz oficial** (fila, columna A/B/C…, obligatoriedad, formato, ejemplo) **calibrada sobre un CSV nuestro que ustedes validen**, no sobre el PDF genérico.

3. **Guía de remisión en factura/boleta:** fila y columnas exactas compatibles con nuestro **FILA 3 actual** y uso de `ATTACH_DOC`.

4. **Orden de compra:** columna exacta compatible con nuestro **FILA 7** (incluidos adicionales de impresión).

5. **Retenciones IGV y retención de segunda categoría:** columnas exactas en **el layout que procesan** (Legacy BP–BR, PDF 2021 AI–AK/BJ–BK, u otras).

6. **Aclarar el bloque de totales:** ¿deben ir en **BB/BC/BD** (PDF 2021) o en **BH/BI/BJ** (Legacy / nuestro CSV)?

7. **Forma de pago:** ¿columna **O** o **BN**?

8. **Formato de fechas:** ¿`dd/mm/yyyy` o `YYYY-MM-DD`?

---

## 6. Borradores de correo

### 6.1 Correo a Contabilidad (copia a jefatura)

**Para:** [Contabilidad]  
**Copia:** [Jefatura]  
**Asunto:** Re: solicitud a eFact (OC, guía y retenciones en PDF) — evaluación de Sistemas

Estimados,

Recibimos el reenvío de la respuesta de **eFact** respecto a la solicitud de que en nuestras facturas electrónicas se visualice en el PDF: **número de orden de compra**, **guía de remisión relacionada** e **indicación de retención** cuando corresponda.

eFact indicó que debemos consignar esa información en el archivo CSV de la siguiente manera (manual CSV 2.1):

- **Orden de compra:** FILA 8, columna B  
- **Guía relacionada:** FILA 4, columna A  

Tras revisar esa orientación frente a **cómo emitimos hoy** y **cómo procesa realmente el servicio de eFact**, les informamos lo siguiente:

---

**1. El requerimiento no es un tema de programación**

Desde Sistemas **sí tenemos contemplada la capacidad** para registrar y enviar **orden de compra, guía de remisión relacionada y retenciones** en el archivo que generamos hacia eFact. **No se trata de que falte desarrollo** ni de que no podamos atender lo solicitado.

El problema es **dónde y cómo** debe ir esa información en el CSV para que eFact la procese y la muestre en el PDF — y ahí las indicaciones recibidas **no coinciden** con el formato que **hoy funciona** en producción.

---

**2. Las indicaciones de eFact no calzan con nuestro proceso actual**

Nuestros comprobantes se generan con una estructura de archivo que **eFact viene procesando correctamente** desde hace tiempo. Al aplicar en prueba las ubicaciones que indicaron (FILA 4 para guía, FILA 8 para orden de compra), **los documentos dejaron de procesar**.

Siendo **fin de mes**, eso es **crítico**: no podemos mantener en producción un cambio que interrumpe la emisión masiva de facturas y boletas.

Adicionalmente, en la respuesta de eFact **no se indicó** cómo debe consignarse la **retención**, pese a que también formaba parte de la consulta original.

---

**3. Lo que necesitamos para avanzar con seguridad**

Para implementar estos campos de forma estable, lo que necesitamos no es criterio contable de nuestro lado, sino que **eFact confirme el mapa exacto** (fila, columna, formato) **compatible con los archivos que ya acepta para nuestro RUC**.

En correos anteriores ya se había señalado que el manual **Factura - CSV 2.1** presenta **inconsistencias** respecto a lo que realmente procesa el OSE. En el análisis técnico que preparamos, el manual **Legacy** también presenta **diferencias** frente a nuestros archivos vigentes.

Mientras no contemos con una definición alineada a nuestra estructura actual, cualquier ajuste “según el PDF” nos deja en **prueba y error**, con riesgo de **rechazos y demoras** en la emisión — justo lo que queremos evitar para poder mostrar en el PDF lo que Contabilidad solicita.

---

**4. Próximo paso y recomendación**

**Procederemos a enviar a eFact un correo técnico**, con el detalle de inconsistencias y un ejemplo de archivo CSV real que **sí procesa correctamente**, solicitando:

- confirmación del layout vigente para nuestro emisor,  
- fila y columna exactas para OC, guía y retención **alineadas a nuestro formato**,  
- y un ejemplo validado por ellos.

**Las copiaremos en ese correo** para que estén enteradas del proceso y del sustento de la solicitud.

Hasta contar con un **manual o matriz que calce con nuestra estructura actual**, **recomendamos no realizar cambios** que puedan provocar errores en nuestros procesos de emisión. Si, aun así, consideran que debemos continuar aplicando las indicaciones de eFact **sin esa base técnica confirmada**, **quedamos atentos a sus indicaciones** para proceder según lo que definan.

---

**5. Solicitud adicional — agentes de retención**

Aprovechamos para pedirles su apoyo con un punto operativo que necesitamos definir antes de activar retenciones en el sistema:

¿Podrían compartirnos el **flujo o informe** de cómo el personal de facturación **identifica que un cliente es agente de retención**? (por ejemplo: consulta en SUNAT, listado interno, marca en el maestro de clientes, aviso de Contabilidad, u otro criterio).

Con ese procedimiento claro podremos alinear el desarrollo a la práctica real del área y evitar cargas incorrectas o incompletas al emitir.

Quedamos atentos a sus comentarios.

Atentamente,

Joel [Apellido]  
[Sistemas / TI]  
Corporación Vasco S.A.C.

---

### 6.2 Correo técnico a eFact (Contabilidad y jefatura en copia)

**Para:** Mesa de Ayuda eFact  
**Copia:** [Contabilidad] / [Jefatura]  
**Asunto:** URGENTE — Ubicación CSV (OC, guía, retención) incompatible con emisor 20513613939 — solicitud de matriz oficial

Estimados señores de eFact — Mesa de Ayuda,

Por medio del presente respondemos al hilo iniciado por **Contabilidad** de Corporación Vasco S.A.C. (RUC **20513613939**), quien solicitó que en el PDF de factura se visualice **orden de compra**, **guía de remisión** e **información de retención** cuando corresponda.

En su respuesta indicaron que, para el **CSV 2.1**, debemos enviar:

| Dato | Indicación eFact |
|------|------------------|
| Orden de compra | FILA 8, columna B |
| Guía relacionada | FILA 4, columna A |
| Retención | *(sin indicar fila/columna)* |

Agradecemos la orientación; sin embargo, **esas ubicaciones no son aplicables en nuestro emisor** tal como procesa hoy su OSE. Adjuntamos **informe técnico de inconsistencias** y **CSV de ejemplo en producción**. Solicitamos su revisión **con urgencia** (fin de mes, alto volumen de comprobantes).

---

**1. Antecedentes — pérdida de tiempo y riesgo operativo**

En **correos previos** ya informamos que el manual **“Factura - CSV 2.1”** presenta **numerosas inconsistencias** respecto a los CSV que su servicio **sí acepta** para nuestro RUC.

Contabilidad nos reenvió su última respuesta (FILA 4 / FILA 8). Implementamos pruebas alineadas al PDF 2021 y el resultado fue **rechazo de procesamiento** — documentos que antes emitían correctamente **dejaron de procesar**. Tuvimos que **revertir** para no detener operaciones en cierre de mes.

Reiteramos: **no es falta de capacidad técnica** para OC, guía o retención; es un **desfase entre la documentación genérica y el layout que su OSE interpreta para nuestro emisor**. Cada ciclo de “ajuste según PDF” sin validación de ustedes **nos genera errores masivos, retrabajo y paradas de facturación**.

---

**2. Estructura real de nuestros CSV (referencia válida)**

Archivos que **procesan correctamente hoy**:

- **9 filas** de cabecera antes de ítems (el PDF 2021 define **10**).
- **Guías y cuotas:** **FILA 3** (no FILA 4). Ejemplo:  
  `TR01-00001844,09,,,CUOTA001,1505.21,07/09/2026,ATTACH_DOC`
- **Orden de compra:** **FILA 7, columna B** (no FILA 8).
- Cierre de ítems: **`FF00FF`** en última línea.
- Fechas en cabecera: **`dd/mm/yyyy`** (PDF 2021 exige `YYYY-MM-DD`).

**Archivo de ejemplo:** `20513613939-01-FR01-00002243.csv` (factura crédito, 3 ítems, guía TR01-00001844).

| Información | Su indicación (CSV 2.1) | **Nuestro CSV que procesa OK** |
|-------------|-------------------------|--------------------------------|
| Guía + cuotas | FILA 4 col. A | **FILA 3** col. A + col. B = 09 |
| Orden de compra | FILA 8 col. B | **FILA 7** col. B |

---

**3. FILA 1 — el manual no describe nuestro mapa de columnas**

Ejemplo real (FR01-00002243), columnas con valor:

| Col. | Valor enviado | Legacy CSV 2.1 | Factura CSV 2.1 |
|------|---------------|----------------|-----------------|
| A | 24/07/2026 | Fecha dd/mm/yyyy ✓ | YYYY-MM-DD ✗ |
| N | 1505.21 | Importe total ✓ | Importe total ✓ |
| O | CREDITO | Descuentos globales ✗ | Forma de pago ✓ |
| BN | *(vacío)* | **Forma de pago** ✗ | *(no aplica)* |
| V, Z, AL | 1275.60 | Totales gravados / base ✓ | Desplazados ✗ |
| BH | 229.61 | Total impuestos ✓ | **Total descuentos AB** ✗ |
| BI | 1275.60 | Total valor venta ✓ | **Anticipo ISC** ✗ |
| BJ | 1505.21 | Total precio venta ✓ | **Base retención renta** ✗ |
| BB, BC, BD | *(vacío)* | — | Total impuestos / valor / precio ✓ |

**Conclusión:** Legacy y PDF 2021 **se contradicen** (ej. forma de pago en **O** vs **BN**; totales en **BH–BJ** vs **BB–BD**). Nuestro CSV **solo procesa** con el mapa actual — no con el PDF 2021 literal ni con Legacy al pie de la letra.

---

**4. Retenciones — sin respuesta en su último correo**

Contabilidad solicitó comprobantes **afectos a retención**. Ustedes **no indicaron** fila/columna.

Tenemos **desarrollo preparado** para retención, pero los manuales ubican esos campos en posiciones distintas:

- Legacy: **BP, BQ, BR** (base, factor, monto retención IGV)  
- PDF 2021: **AI, AJ, AK** (y retención 2da categoría en **BJ, BK**)

Sin confirmación de su OSE, activar retención según PDF **repite el riesgo de paralizar emisiones**.

---

**5. Solicitud formal**

Para atender a Contabilidad (OC, guía y retención **visibles en PDF**) **sin nuevos rechazos**, solicitamos:

1. **Confirmar el layout CSV vigente** para RUC 20513613939: ¿9 o 10 filas? ¿CSV 2.1, Legacy u otro parser?  
2. **Matriz oficial** fila × columna (A, B, C…) para:  
   - orden de compra,  
   - guía de remisión (código 09),  
   - retención IGV y, si aplica, retención de segunda categoría.  
3. **Ejemplo CSV validado por ustedes** partindo de nuestro archivo `20513613939-01-FR01-00002243.csv`, indicando qué columnas debemos cambiar — no solo referencia al PDF genérico.  
4. Aclarar si la visualización en PDF depende **solo del CSV** o requiere configuración en su plataforma.  
5. Confirmar **formato de fechas** aceptado (`dd/mm/yyyy` vs `YYYY-MM-DD`).

**Adjuntos:**

- Informe técnico de inconsistencias (secciones 1 a 5 de este documento)  
- CSV ejemplo: `20513613939-01-FR01-00002243.csv`

Insistimos en la **urgencia**: cada indicación no validada nos obliga a pruebas en producción, **rechazos** y **pérdida de tiempo** en un periodo de emisión masiva.

Quedamos atentos a su respuesta o a una **reunión técnica a la brevedad**.

Atentamente,

Joel [Apellido]  
[Sistemas / TI] — Corporación Vasco S.A.C.  
RUC 20513613939

---

*Documento preparado para envío. Versión: 30/07/2026.*
