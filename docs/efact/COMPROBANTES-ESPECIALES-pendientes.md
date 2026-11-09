# Comprobantes especiales — pendientes y plan (revisión del 9-nov-2026)

**Estado:** solo revisión y documentación. **No se implementa nada todavía.**
**Alcance revisado:** cómo funciona hoy cada punto en el sistema y cómo se haría para el tipo **GRATUITA** (y futuros tipos).
**Relacionado:** `COMPROBANTES-ESPECIALES-diseno.md` (módulo), `GRATUITAS-codigos-afectacion-sustento.md` (para Contabilidad).

## 0. Resumen y orden sugerido

| # | Punto | Estado hoy | Depende de | Tamaño |
|---|-------|------------|------------|--------|
| 2 | Enviar a eFact | **Hecho en código** (copia el CSV a la carpeta que vigila eFact). Falta la prueba real | Prueba en el entorno con acceso a la carpeta de eFact | Hecho / probar |
| 3 | Corregir lo que falle | **Proceso definido** (CSV + error → eFact → corregir → repetir). No empezó | Punto 2 | Variable |
| 1 | Cuenta por cobrar | **No existe.** Probablemente **no hace falta** (total 0.00); decisión de Contabilidad | Respuesta de Contabilidad | S (si se pide) |
| 4 | Excel de registro de ventas | **No incluye** estos comprobantes (solo lee `ventajf`) | Puntos 2–3 y criterio de Contabilidad | M |
| 5 | Pase de datos a SISCONT | **No incluye** estos comprobantes (solo lee `ventajf`) | Puntos 2–3, 1 y el asiento contable | M–L |

Orden: **2 → 3** (que eFact acepte) → **decisión del punto 1** → **4** → **5**. Los puntos 4 y 5 no se tocan hasta que eFact acepte el comprobante, como se acordó.

**Por qué 4 y 5 no salen solos:** el módulo guarda en sus propias tablas (`comp_especialjf`, `comp_especial_detjf`), y el registro de ventas y SISCONT leen **solo `ventajf`**. Es el costo de haberlo aislado del flujo de ventas, a propósito: no se rompe nada, pero cada reporte contable hay que extenderlo explícitamente.

---

## 1. Generar la cuenta por cobrar

### Cómo funciona hoy (flujo de ventas)
- Al facturar un pedido/guía se llama `ControladorFacturacion::ctrRegistrarCuentaCorriente()` (`controladores/facturacion.controlador.php`, ~línea 4389), que inserta con `ModeloFacturacion::mdlGenerarCtaCte()` (`modelos/facturacion.modelo.php`, ~línea 516) en la tabla **`cuenta_ctejf`**.
- Campos que escribe: `tipo_doc`, `num_cta`, `cliente` (código de `clientesjf`), `vendedor`, `fecha`, `fecha_ven` (fecha + días de la condición de venta), `monto`, `cod_pago`, `doc_origen`, `usuario`, `saldo`, `usureg`, `pcreg`. Los movimientos se distinguen por `tip_mov` (`+` cargo, `-` abono/pago).
- La cuenta corriente alimenta cobranza, estado de cuenta, dashboards CxC, letras/canje y SISCONT.

### Lo que implica para un obsequio
- El comprobante tiene **total S/ 0.00**: no hay nada que cobrar. Una cuenta por cobrar de **monto 0 y saldo 0** no aporta cobranza y sí ruido en reportes.
- `cuenta_ctejf` exige `cliente` (código de `clientesjf`) y `vendedor`. Los receptores del módulo se escriben por DNI/RUC y **no siempre existen en `clientesjf`**; habría que crearlos o permitir nulos (riesgo para reportes que hacen JOIN).
- La pantalla **Errores** (`mdlErrores`) marca como ERROR los documentos de `ventajf` sin cuenta corriente. Los comprobantes especiales **no están en `ventajf`**, así que no se ven afectados.

### Opciones
| Opción | Qué hace | Recomendación |
|--------|----------|---------------|
| **A. No generar** | Un obsequio no crea cuenta por cobrar | **Recomendada para GRATUITA**: no hay monto a cobrar |
| B. Registro 0 / 0 | Inserta en `cuenta_ctejf` con `monto = 0`, `saldo = 0`, cancelado, solo como rastro | Solo si Contabilidad lo exige para conciliar numeración |
| C. Para tipos futuros | Un tipo que sí tenga importe a crédito (p. ej. factura especial) **sí** debe crear la cuenta | Se diseña con ese tipo |

### Si se decide B o C (cómo se haría)
1. Reutilizar `mdlGenerarCtaCte()` (misma SQL, no duplicar) desde el controlador del módulo, **al enviar** el comprobante o al crearlo (decidir).
2. Confirmar qué valores usan hoy facturas y boletas en `tipo_doc` y `cod_pago` de `cuenta_ctejf` (el flujo actual pasa el tipo del documento) y copiarlos.
3. Resolver `cliente` y `vendedor`: buscar el receptor en `clientesjf` por documento; si no existe, decidir entre crearlo o usar un cliente genérico de obsequios.
4. Verificar que cobranza, estados de cuenta y la pantalla Errores no se rompan.

**Pregunta para Contabilidad:** ¿necesitan cuenta por cobrar para un comprobante de total 0.00?

---

## 2. Enviar a eFact

### Hecho
- `ControladorComprobantesEspeciales::ctrEnviar()` arma el CSV con nuestra estructura y lo deja en la carpeta que vigila eFact (`DESTINO_INVOICE` / `DESTINO_BOLETA`, la misma de las ventas). Marca el comprobante como **ENVIADO**. También existe "Ver CSV" (no envía nada) y "Marcar baja".

### Pendiente
1. **Primera prueba real** (boleta a nombre de Joel, serie provisional BG01, baja inmediata). La prueba solo vale en el entorno con acceso a la carpeta de eFact (en desarrollo/Docker la carpeta destino es local y no la lee eFact).
2. **Estado real del comprobante.** Hoy el módulo **no sabe** si eFact lo aceptó: solo registra que dejó el archivo. Las ventas normales muestran SIN ENVIAR / ENVIADO / BAJA en **Procesar comprobante electrónico** (`procesar-ce`, columna `ventajf.facturacion`: `0` sin enviar, `2` enviado, `1`/`4` baja) y tienen el botón **Consulta SUNAT** (valida el comprobante con RUC, serie, correlativo, fecha y monto; responde ACEPTADO / AUTORIZADO / ANULADO / NO EXISTE).
3. **Mejora propuesta:** botón **"Consultar estado"** en el listado, reutilizando la misma consulta SUNAT de `procesar-ce` (`ajax/facturacion.ajax.php`, parámetros `tipoConsulta`, `rucConsulta`, `serieConsulta`, `correlativoConsulta`, `emisionConsulta`, `montoConsulta`), y guardar el resultado (nuevo estado `ACEPTADO` / `OBSERVADO`). Para un obsequio el monto a consultar es **0.00**; hay que verificar que la consulta lo acepte.
4. Si eFact rechaza, **no hay mensaje de error en el sistema** (igual que las ventas): el error se obtiene de eFact/su portal y se registra a mano en `pruebas-comprobantes-especiales/REGISTRO.md`.

---

## 3. Corregir lo que falle

Proceso acordado (ver `pruebas-comprobantes-especiales/GUIA-PRUEBA.md`):
1. Enviar la boleta de prueba → revisar en eFact → **dar de baja** el comprobante en su portal y pulsar **Marcar baja**.
2. Si falla: guardar el **CSV exacto + el mensaje de error** y enviarlos a eFact preguntando qué columna corregir. Anotar cada intento en `REGISTRO.md`.
3. Corregir **un campo a la vez** (solo la parte de operación gratuita; la estructura del CSV no se mueve) y repetir.
4. Repetir con una **factura** (RUC, serie FG01).
5. Al cerrar, actualizar el diseño (§6) y la guía con lo que eFact confirmó.

**Dudas ya identificadas (⚠, columnas a confirmar con la prueba):**
- Columna del IGV informativo en cabecera: hoy **AO** (Legacy); en el layout 2021 es **T**.
- Si la columna **W** de la línea (hoy vacía) debe llevar valor.
- Si **Z** y **AL** de cabecera van en `0.00` o vacías.
- Aceptación de dirección `-` y ubigeo vacío cuando el receptor es una persona con DNI.
- Aceptación de **total 0.00** en nuestro layout (el ejemplo de eFact, en layout 2021, lo acepta).

Cada intento consume un correlativo de la serie provisional; no reiniciarla si algún comprobante llegó a eFact (§ guía de prueba).

---

## 4. Excel de registro de ventas

### Cómo funciona hoy
- Archivo: `vistas/reportes_excel/rpt_registro_ventas.php` (PHPExcel, funciones `mysql_*`), abierto desde el botón **Registro Ventas** de `procesar-ce` (`vistas/js/facturacion.js` ~línea 3307) con `inicio`, `fin`, `guias`, `remision`.
- **Lee solo `ventajf`**, tipos `S02` (boleta), `S03` (factura), `S05` (nota débito), `E05` (nota crédito); con `guias=1` agrega `S70`; con `remision=1` solo `S01`.
- Columnas A–U: fecha, tipo de documento (1, 3, 7, 8), documento, datos de origen (notas), documento y nombre del cliente, vendedor, ventas en USD, tipo de cambio, neto, descuento, Q (`0` fijo), **base imponible**, **impuesto (IGV)**, **total** y estado (GENERADO / ANULADO / otros). Cierra con totales.

### Lo que falta
Los comprobantes especiales **no aparecen**. Haría falta:
1. Una segunda consulta (o `UNION`) sobre `comp_especialjf`: fecha, tipo (`1` factura / `3` boleta), documento `SERIE-NÚMERO`, documento y nombre del receptor, vendedor vacío, y el **estado** del comprobante (incluir ANULADO/BAJA visibles, como hoy se ven los anulados).
2. Decidir **cómo se muestra el valor**: el total es 0.00, pero hay **valor gratuito** e **IGV informativo**. Opciones: (a) una columna nueva "OP. GRATUITA" y el IGV fuera de los totales; (b) mostrar base e IGV como gravada.
3. Un parámetro o casilla nueva (p. ej. `especiales=1`) en el modal de **Registro Ventas**, para incluirlos o no, sin alterar el reporte actual.
4. Verificar que los totales del pie del Excel sigan cuadrando.

### ⚠ Pregunta clave para Contabilidad (impacto tributario)
Un obsequio con IGV es un **retiro de bienes gravado**: **el IGV lo paga Vasco**. ¿Cómo debe verse en el registro de ventas: **base imponible e IGV como operación gravada** (para que el IGV entre al débito fiscal) o como **operación gratuita sin importe**? De eso depende cómo se arma la columna; el Excel no debe ocultar un IGV que Vasco sí debe declarar.

---

## 5. Pase de datos a SISCONT

### Cómo funciona hoy
- Pantalla **Procesar comprobante electrónico** → **Transferir Ventas / Canje / Cancelaciones / Clientes SISCONT** (`vistas/modulos/facturacion/procesar-ce.php`).
- **Ventas:** `ControladorContabilidad::ctrGenerarVentasSiscont()` (`controladores/contabilidad.controlador.php`) usa `ModeloContabilidad::mdlVentasSiscontB()` (`modelos/contabilidad.modelo.php`), que lee **solo `ventajf`** (tipos `S02`, `S03`, `E05`, `S05`).
- Genera por cada documento un **asiento de varias líneas** en un archivo de registros de ancho fijo `V<inicio><fin>.txt`: origen `02`, voucher correlativo por mes, **cuenta por cobrar** `121201` (soles) / `121202` (dólares) al **debe** por el total, **IGV `40111`** al **haber**, y la **cuenta de ventas** (según `ventajf.cuenta` → tabla `TCUE`) al haber por el neto. Las notas de crédito invierten el signo.
- Además crea `VB<inicio><fin>.bat` (`MSISCONT.EXE VASCO2025 …`) y copia ambos a la carpeta de red de SISCONT (ruta fija en el código). La numeración de vouchers se guarda por año/mes con `mdlActualizarCorrelativo(..., "valor_1")`.
- Los clientes se transfieren aparte (`ctrGenerarClientesSiscont`); un receptor nuevo (DNI/RUC no registrado en `clientesjf`) **no existe en SISCONT** aunque el comprobante exista.

### Lo que falta
Los comprobantes especiales **no se exportan**, y el asiento de ventas **no sirve tal cual** para un obsequio: no hay cuenta por cobrar (no se cobra) ni ingreso por venta. Un retiro lleva otro asiento (gasto según destinatario, salida de existencias e **IGV `40111` por el retiro**). **Las cuentas las define Contabilidad**; aquí no se asumen.

### Opciones de implementación
| Opción | Descripción | Riesgo |
|--------|-------------|--------|
| **A. Transferencia propia (recomendada)** | Un botón nuevo "Transferir Gratuitas SISCONT" con su propio generador y su propio asiento; reutiliza el formateo de ancho fijo y la forma de entregar `.txt` + `.bat` | Bajo: no toca el archivo de ventas que hoy funciona |
| B. Ampliar `mdlVentasSiscontB` con `UNION` | Una sola transferencia | Alto: mezcla asientos distintos en el flujo de ventas |

Puntos a resolver con A: **numeración de vouchers** (¿continúa el mismo contador mensual o uno propio?), **cuentas de gasto por tipo de destinatario** (trabajador / cliente / proveedor / publicidad), **clientes nuevos** en SISCONT y dependencia con el punto 1 (si no hay cuenta por cobrar, no hay `121201`).

---

## 6. Preguntas abiertas para Contabilidad

Están redactadas **con opciones para marcar** en la sección «Preguntas para Contabilidad» de `GRATUITAS-codigos-afectacion-sustento.md` (preguntas 1–9; documento en formato Obsidian, listo para exportar a PDF). Las que corresponden a estos pendientes:
- Punto 1 (cuenta por cobrar): pregunta **7**.
- Punto 4 (registro de ventas): pregunta **8**. Contabilidad debe dar el lineamiento (no se ofrece "lo define el asesor").
- Punto 5 (SISCONT): pregunta **9**.
- Para emitir: preguntas **1–6** (códigos, valor, IGV de la compra, comprobante a clientes).

**Ya no se preguntan a Contabilidad** (decisiones de Sistemas, informadas en el documento o resueltas internamente):
- **Serie:** se usará serie propia, **BG01** (boletas) y **FG01** (facturas); está informado en el documento.
- **Autorizante:** el sistema registra automáticamente el usuario que emite; no se pide autorizante.
- **Salida de almacén:** el módulo no mueve stock.
- **Prueba con eFact:** se coordina directamente (comprobante a nombre de Joel, baja inmediata en el portal de eFact).
- **Numeración de vouchers en SISCONT:** queda como decisión pendiente de implementación del punto 5 (¿continúa la numeración mensual de ventas o es propia?); se resuelve al diseñar ese punto con el asiento.

Cuando Contabilidad responda, transcribir las respuestas en `GRATUITAS-respuestas-contabilidad.md` (crear al recibirlas) y actualizar el diseño.

## 7. Archivos a tocar cuando se retomen

| Punto | Archivos |
|-------|----------|
| 1 | `controladores/comprobantes-especiales.controlador.php`, `modelos/comprobantes-especiales.modelo.php` (reutilizar `ModeloFacturacion::mdlGenerarCtaCte`) |
| 2 | `ajax/comprobantes-especiales.ajax.php`, `vistas/js/comprobantes-especiales.js` (botón "Consultar estado"), reutilizar `ajax/facturacion.ajax.php` |
| 3 | `pruebas-comprobantes-especiales/REGISTRO.md`, controlador (`lineasGratuita`) |
| 4 | `vistas/reportes_excel/rpt_registro_ventas.php`, modal de **Registro Ventas** en `procesar-ce.php`, `vistas/js/facturacion.js` |
| 5 | `controladores/contabilidad.controlador.php`, `modelos/contabilidad.modelo.php`, `procesar-ce.php` (botón nuevo) |
