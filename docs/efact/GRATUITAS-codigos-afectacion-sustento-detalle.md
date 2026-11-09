# Obsequios: por qué usamos cada código de afectación del IGV

**Para:** Contabilidad (y su asesor tributario) · **De:** Sistemas · **Fecha:** 9 de noviembre de 2026
**Propósito:** explicar *por qué* el comprobante de un obsequio lleva ciertos códigos y no otros, con sustento en normas de SUNAT, para que Contabilidad pueda validarlo o corregirlo.
**Importante:** esto es el criterio que se desprende de las fuentes citadas, **no es asesoría tributaria**. Lo marcado "⚠ por confirmar" no pudimos verificarlo.

---

## 1. Resumen en 30 segundos

1. Para el IGV, **regalar un bien es "retirarlo" y el retiro se trata como una venta gravada** (aunque no se cobre nada).
2. Por eso el obsequio sale como **operación gratuita gravada**: código **11–16**, tributo **9996 (Gratuito)**, con el IGV mostrado solo como **informativo**. El **importe total es 0.00**.
3. **Vasco asume ese IGV**: no se le puede trasladar a quien recibe el regalo, y quien lo recibe no lo usa como crédito fiscal.
4. Los códigos **inafectos (31–37)** y **exonerado (21)** son para casos con sustento legal específico. **No son una alternativa "más cómoda"**.
5. El sistema (y eFact/SUNAT) **solo valida que el CSV sea coherente**, no que el código sea el correcto. Esa decisión es de Contabilidad.

## 2. Base legal (verificada)

Fuente principal: **SUNAT, Informe N.° 096-2013-SUNAT/4B0000** (27-may-2013), que cita el TUO de la Ley del IGV (D.S. 055-99-EF) y el Reglamento (D.S. 029-94-EF). Texto leído del PDF oficial.

| Qué dice | Dónde |
|---|---|
| El IGV grava la venta en el país de bienes muebles | Art. 1 inc. a) TUO |
| **Se entiende por venta el retiro de bienes** que efectúa la empresa, incluso los hechos como descuento o bonificación, salvo las excepciones de la Ley y su reglamento | Art. 3 inc. a) num. 2 TUO |
| **Retiro** = todo acto por el que se transfiere la propiedad de bienes a título gratuito, **"tales como obsequios, muestras comerciales y bonificaciones"** | Art. 2 num. 3 inc. c) Reglamento |
| **El IGV del retiro no se puede deducir como crédito fiscal ni como costo o gasto por el adquirente** | Art. 20 TUO |
| **El IGV del retiro en ningún caso se traslada al adquirente** | Art. 2 num. 6 Reglamento |
| Quien paga el IGV es el que retira ("vendedor"), porque al ser gratuito no se genera valor agregado; por eso la ley lo considera venta gravada | Análisis del Informe 096-2013 |

**Excepciones a "retiro" que el mismo informe lista (no hay IGV, no se emite obsequio):**
- Bienes entregados a trabajadores **como condición de trabajo**, siempre que sean **indispensables** para prestar el servicio, o cuando la entrega la disponga una **ley** (ej. uniformes, EPP).
- Insumos, materias primas y bienes intermedios usados en la producción; entrega a un tercero para fabricar bienes encargados; retiro por pérdida/destrucción acreditada; consumo propio necesario para operaciones gravadas.

**Y lo que el informe dice que SÍ es retiro (gravado):**
- **Entrega a trabajadores de bienes de libre disposición** que no son necesarios para su trabajo.
- Entrega pactada por **convenio colectivo** que **no** sea condición de trabajo ni indispensable.

> Consecuencia para nosotros: una **canasta navideña o una casaca de cumpleaños** a un trabajador es de libre disposición → **retiro gravado**. Es lo mismo que muestran los ejemplos de otras empresas (Autonort: casaca por cumpleaños, IGV 18 % informativo; Virazon: canasta a trabajadores).

## 3. Los códigos (Catálogo 07 de SUNAT, versión de la R.S. 245-2017)

| Cód. | Descripción oficial | Se usa para |
|------|---------------------|-------------|
| 10 | Gravado – Operación onerosa | Venta normal (no es obsequio) |
| **11** | Gravado – Retiro por premio | Premios de sorteos/concursos |
| **12** | Gravado – Retiro por donación | Donaciones reales (a una entidad, institución, etc.) |
| **13** | Gravado – Retiro | **Obsequio general** a clientes/proveedores |
| **14** | Gravado – Retiro por publicidad | Obsequio promocional con fines publicitarios (ej. con logo) |
| 15 | Gravado – Bonificaciones | Bonificación ligada a una venta ("compra 10, lleva 1") |
| **16** | Gravado – Retiro por entrega a trabajadores | **Regalos a trabajadores** (libre disposición) |
| 21 | Exonerado – Transferencia gratuita | Solo si el bien está exonerado del IGV |
| 31–36 | Inafecto – Retiro por bonificación / retiro / muestras médicas / convenio colectivo / premio / publicidad | **Excepcional**: requiere sustento legal específico (ver §5) |

(El manual de eFact además menciona el código 37; no aparece en la versión del anexo que leímos.)

**Regla técnica que acompaña a cada código** (manual eFact Legacy CSV 2.1):

| Si la afectación es… | Código de tributo | % IGV | IGV de la línea |
|---|---|---|---|
| 11 a 16 (gravado) | **9996** Gratuito | 18 | **Sí, informativo** (18 % del valor) |
| 21, 31 a 37 | **9996** Gratuito | 0 | 0.00 |

Por eso el obsequio "con IGV" **no es otro formato**: es otra afectación. Y por eso los CSV de ejemplo de eFact "con IGV" usan 14 y "sin IGV" usan 32.

## 4. Qué código por quién (propuesta de Sistemas, a validar)

| Destinatario / situación | Código propuesto | Razón |
|---|---|---|
| **Trabajadores**: canasta, casaca, regalo de cumpleaños o fiesta | **16** | Libre disposición → retiro gravado (Informe 096-2013) |
| Trabajadores: uniforme/EPP indispensable para trabajar | **no se emite obsequio** | Condición de trabajo → no es retiro |
| **Clientes / proveedores**: obsequio de fin de año, atención | **13** | "Retiro" general; es un obsequio |
| Clientes / proveedores: producto con logo, material promocional | **14** | Retiro por publicidad |
| Premio de sorteo o concurso | **11** | Retiro por premio |
| Donación a una entidad (no a un cliente) | **12** | Retiro por donación |
| Regalo atado a una venta ("1 gratis por 10") | **15** | Bonificación; normalmente va en la venta (fuera de este módulo) |
| Cualquier caso que Contabilidad quiera tratar como **inafecto (31–37)** | 31–37 | Solo con el sustento que indique el contador (§5) |

> **Corrección:** en documentos anteriores de Sistemas se propuso 12 para "regalos a clientes y proveedores" y 34 (convenio colectivo) como inafecto para trabajadores. Con la lectura del Informe 096-2013, **13 es más adecuado para un obsequio general** y **el convenio colectivo que no es condición de trabajo sí es retiro gravado**, no inafecto. Quedó corregido.

## 4b. Casos concretos de Vasco 2026

| Caso | Comprobante | Código | Razón |
|---|---|---|---|
| **Panetones a clientes** (comprados para regalar) | Factura (RUC) o boleta (DNI), **uno por cliente** | **13** (14 si lleva logo/fin publicitario de Vasco) | Obsequio a terceros = retiro gravado |
| **Canastas de víveres a trabajadores** (compradas para regalar) | Boleta con DNI, **una por trabajador** | **16** | Libre disposición, no indispensable para trabajar → retiro gravado (Informe 096-2013); equivale al ejemplo Virazon |

Ambos: IGV informativo (18 % del valor referencial), importe total 0.00, leyenda 1002.

**Preguntas adicionales por ser bienes comprados (⚠ por confirmar con el contador):**
1. ¿Se usa como crédito fiscal el IGV de la compra de panetones y canastas, y cómo se combina con el IGV del retiro?
2. Panetones a clientes: ¿cómo se trata como gasto (atención/representación) y qué límites de deducibilidad aplican?
3. ¿Con qué valor se emite cada obsequio (costo de compra sin IGV, valor de mercado, otro)?

## 5. Qué NO pudimos verificar (⚠ por confirmar con el contador)

1. **Cuándo aplican realmente los códigos inafectos 31–36** (ej. 33 muestras médicas, 34 convenio colectivo, 36 publicidad) y con qué requisitos. El informe 096-2013 cita el Reglamento de 1994; **la redacción vigente puede tener más excepciones** (muestras comerciales, bienes promocionales bajo ciertos requisitos). Hasta que el contador lo confirme, **la propuesta es usar siempre el gravado (11–16)**.
2. **La base del IGV**: qué valor se declara en un retiro (valor de mercado, costo, precio de lista). El sistema pedirá un *valor referencial unitario*; Contabilidad debe indicar de dónde sale.
3. **Tratamiento del gasto en el Impuesto a la Renta** (límites de deducibilidad de atenciones a clientes/proveedores, remuneración para trabajadores, etc.). No lo tratamos aquí.
4. **El tratamiento si el receptor no tiene RUC/DNI** y el monto límite para boleta.

## 6. ¿Qué pasa si se elige mal? (por qué sí importa)

- Si **se emite sin IGV un retiro que era gravado**, el IGV de ese retiro **queda sin declarar** y puede generar reparo de SUNAT con intereses y multas.
- Si **se emite con IGV algo que era inafecto**, se declara IGV de más sin necesidad.
- **eFact y SUNAT no detectan el error de criterio**: aceptan cualquier combinación coherente (ej. 32 + 9996 + 0 %). Por eso el control es humano y el código **no debería elegirse "por comodidad"**.
- Quien recibe el regalo **no puede usar el IGV como crédito fiscal ni gasto** (Art. 20 TUO): es relevante cuando el receptor es un proveedor o cliente con RUC.

## 7. Cómo queda reflejado en el comprobante

| Elemento | Obsequio con afectación 11–16 |
|---|---|
| Importe total | 0.00 ("CERO Y 00/100 SOLES") |
| Op. Gratuitas | Valor referencial × cantidad |
| IGV mostrado | 18 % del valor, **informativo** |
| Leyenda | "TRANSFERENCIA GRATUITA DE UN BIEN Y/O SERVICIO PRESTADO GRATUITAMENTE" |
| Línea | Afectación 11–16, tributo 9996, valor de venta = valor referencial |

Detalle de columnas del CSV: `COMPROBANTES-ESPECIALES-diseno.md` §2c y §6.

## 8. Preguntas para Contabilidad / su asesor

1. ¿Confirman que los obsequios a **trabajadores** (canastas, regalos de cumpleaños) se tratan como **retiro gravado (16)**?
2. ¿Los obsequios a **clientes y proveedores** van como **13** (general) o **14** (publicidad), y cuándo se considera **donación (12)**?
3. ¿Hay algún caso concreto de Vasco donde apliquen **inafecto (31–36)**? Si sí, ¿con qué sustento?
4. ¿Qué **valor** se toma como referencial (precio de lista, costo, valor de mercado)?
5. El sistema registra automáticamente el usuario que emite cada comprobante, además del motivo y el destinatario. ¿Necesitan además un autorizante formal por cada obsequio?

## 9. Fuentes

- SUNAT, [Informe N.° 096-2013-SUNAT/4B0000](https://www.sunat.gob.pe/legislacion/oficios/2013/informe-oficios/i096-2013.pdf) — retiro de bienes, Art. 3 TUO IGV, Art. 2 Reglamento, Art. 20 TUO.
- SUNAT, [R.S. 245-2017, Anexo — Catálogo No. 07](https://www.sunat.gob.pe/legislacion/superin/2017/anexoE-245-2017.pdf) — códigos de tipo de afectación del IGV.
- eFact, manual *Legacy Factura CSV 2.1* (`docs/efact/Legacy Factura CSV 2.1.pdf`) — reglas por columna.
- TUO de la Ley del IGV (D.S. 055-99-EF) y Reglamento (D.S. 029-94-EF) — texto vigente a consultar en el portal de SUNAT.
