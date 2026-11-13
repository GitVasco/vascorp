# Facturas de exportación — Diseño por fases

**Emisor:** Corporación Vasco S.A.C. — RUC 20513613939
**Fecha:** 13 de noviembre de 2026
**Estado:** fases 1, 2 y 3A implementadas, falta probarlas con una factura real. Se avanza por fases y se prueba cada una antes de seguir.

**Regla principal:** **no modificar código actual.** Todo es nuevo (funciones, tablas, vistas). Lo existente solo se **llama**, no se edita. El flujo actual con serie `FX` sigue funcionando hasta que el nuevo esté probado.

---

## Dónde nos quedamos (13-nov-2026)

**Esperando:** que almacén prepare un pedido de exportación real para probar con él.

**Hecho:**

| Fase | Qué quedó | SQL |
|------|-----------|-----|
| 1 | País del cliente en Clientes («Cliente del exterior»), pantalla Configuración de exportación con las pestañas Clientes, Modelos/precio lista 1 y Códigos SUNAT | `exportacion-fase1.sql` ✅ ejecutado |
| 2 | Pestaña **Archivo eFact (prueba)**: compara el CSV actual con el nuevo de una factura FX y los descarga. No envía nada | `exportacion-fase2.sql` ⏳ ejecutar (precarga los 5 códigos SUNAT) |
| 3A | Pedidos: marca «EXP» y bandera en los listados, aviso en crear pedido, solo series FX en Facturar, control en el servidor y «EXPORTACIÓN · país» en el título del pedido impreso | — |

**Prueba pendiente, con el pedido real:**

1. Lista de pedidos: el cliente aparece con «EXP» y su bandera. El pedido impreso dice «EXPORTACIÓN · país» en el título.
2. Facturar → Factura: solo aparece la serie FX. Emitir como siempre.
3. Configuración de exportación → Archivo eFact (prueba): comparar esa factura. Las diferencias esperadas son fila 5 H, fila 6 H, columna R y el país.
4. Opcional: mandar a eFact a mano el archivo «nuevo» para ver si lo acepta.
5. Anotar el resultado en la §6 Registro de pruebas.

**Siguiente (3B), cuando la prueba salga bien:**

- Cuenta corriente en USD con tipo de cambio actualizable (`exp_cta_monedajf`).
- Envío a eFact del CSV nuevo en lugar del actual.

Después siguen la Fase 4 (formato de impresión de la factura), la Fase 5 (cobranza en USD, en pausa) y la Fase 6 (retiro del flujo antiguo).

**Archivos nuevos:**

- `modelos/exportacion.modelo.php`
- `controladores/exportacion.controlador.php`
- `ajax/exportacion.ajax.php`
- `ajax/exportacion-pedidos.ajax.php`
- `vistas/modulos/facturacion/exportacion-config.php`
- `vistas/js/exportacion.js`
- `vistas/js/exportacion-pedidos.js`

**Ganchos mínimos en archivos existentes:**

- `index.php`
- `vistas/plantilla.php`
- `vistas/modulos/menu.php`
- `vistas/modulos/facturacion/clientes.php`
- `vistas/js/clientes.js`
- `controladores/clientes.controlador.php`
- `controladores/facturacion.controlador.php` (bloque de control en `ctrFacturarN`)
- `vistas/reportes_ticket/impresion_pedido_v2.php` (título)

---

## 1. Situación actual (por qué cambiar)

| Punto | Hoy | Problema |
|---|---|---|
| Detección | La serie empieza con `FX` → `exportacion = 1`, `tipo_moneda = 2` | Depende de elegir bien la serie; no hay validación |
| Flujo | Pedido normal → venta, stock y cuenta corriente | Correcto en idea, pero sin controles propios |
| Cuenta corriente | `cuenta_ctejf` **sin moneda** | El monto en USD queda como si fuera soles |
| CSV | `ctrGenerarFEFacBolB` (0200, afectación 40, tributo 9995) | Valores forzados |
| País del cliente | Fijo: `ESPAÑA` → `ES`, el resto `PE` | No sirve para otros países |
| Documento cliente | Impresión fuerza `00000000` | Datos del cliente extranjero no se ven |
| Partida arancelaria | Arreglo `$codigo_arancel` en `config.php` por línea; si falta, "No encontrado" | No lo usan, pero sale texto inválido |
| Monto en letras | Reemplaza "SOLES" por "DOLARES AMERICANOS" | Funciona, pero es un parche |
| Conocimiento | Solo lo conoce una persona | Riesgo operativo |

## 2. Decisiones tomadas

| Tema | Decisión |
|---|---|
| Precios | **Lista de precios 1 (`precio1`)**, exclusiva para exportación (el pedido ya la calcula sin IGV) |
| Stock | Descuenta del **almacén principal**, como venta normal |
| Cuenta corriente | **Sí**, en **USD** |
| Origen | **Desde un pedido**, para que el almacén lo prepare |
| Cliente | A eFact se envía **sin documento** (única forma que procesa); sus datos reales **sí se imprimen** |
| Datos aduaneros (incoterm, DAM, puerto) | No se piden en esta etapa |
| Partida arancelaria | Opcional; se contempla pero no bloquea |
| Notas de crédito de exportación | Fuera de alcance por ahora |
| Acceso | Quien tenga permiso al módulo de facturación |

## 3. Dónde vive

Submenú **Comprobantes especiales → Exportación** (ver `COMPROBANTES-ESPECIALES-diseno.md` §11). El menú Comprobantes especiales se muestra si el usuario puede usar las gratuitas (usuarios 70 y 6) **o** tiene el módulo de facturación. Cada uno ve solo sus opciones: las gratuitas siguen restringidas y Exportación es para todos los de facturación.

**Clientes:** se usa la **misma lista de clientes** (`clientesjf`), que ya tiene los tipos de documento extranjeros, la dirección, el correo y la lista de precios. Solo se agrega el **país** (`clientesjf.pais_iso`; vacío = Perú) en la pantalla Clientes.

**Excepción consciente:** a diferencia de la gratuita, la exportación **sí** registra venta, stock y cuenta corriente, porque son prendas de línea. Lo hace llamando a las funciones existentes del flujo normal, sin modificarlas.

## 4. Fases

### Fase 1 — Configuración

- Constantes nuevas en `config.php` (con valor por defecto si no existen):
  - `EXPORTACION_SERIE` (serie FX vigente en `talonariosjf`)
  - `EXPORTACION_LISTA` = `precio1`
  - `EXPORTACION_ALMACEN` = `01`
- Columna nueva y opcional **`clientesjf.pais_iso`** (ISO alfa-2; NULL = Perú). El SQL precarga `ES` en los clientes con ubigeo "ESPAÑA".
- Tabla nueva **`exp_partida_lineajf`**: `linea`, `partida`, `descripcion`, `activo`. Vacía por defecto.
- Pantalla de configuración: muestra la configuración vigente, los clientes del exterior (solo consulta) y las partidas (editables).

**Prueba:** asignar el país a un cliente de exportación real en Clientes y verlo en Exportación → Configuración.

**Implementado (13-nov-2026), pendiente de prueba:**
- SQL: `docs/sql/exportacion-fase1.sql` (idempotente).
- Archivos nuevos: `modelos/exportacion.modelo.php`, `controladores/exportacion.controlador.php`, `ajax/exportacion.ajax.php`, `vistas/modulos/facturacion/exportacion-config.php`, `vistas/js/exportacion.js`.
- **Clientes** (cambios pequeños, solo de agregar):
  - `clientes.php`: campo «País» en el alta y la edición. **Solo aparece si la columna ya existe.**
  - El número de documento al editar pasa de `number` a `text`, para aceptar documentos extranjeros con letras (el alta ya era `text`).
  - `clientes.controlador.php`: tras un alta o edición correcta, llama a `ControladorExportacion::ctrGuardarPaisCliente` (actualiza solo `pais_iso`). No cambian `mdlIngresarCliente` ni `mdlEditarCliente`.
  - `clientes.js`: carga el país al editar.
- Cableado: `index.php`, `vistas/plantilla.php` y `vistas/modulos/menu.php` (menú Comprobantes especiales con las condiciones de §3).
- Acceso: `$_SESSION["facturacion"] == 1`.
- Serie: si no se define `EXPORTACION_SERIE`, se toma la **única** serie `FX…` de `talonariosjf`; si hay varias, la pantalla avisa.
- La pantalla avisa qué clientes ya tienen facturas de exportación y aún no tienen país, y marca a los clientes del exterior que no tienen lista `precio1`.
- Pestaña **Modelos y precio lista 1**: modelos activos (imagen, modelo, descripción, marca, línea, tipo, unidad) con `preciojf.precio1` editable en la misma tabla.
  - Se muestra la última fila de `preciojf` del modelo. Al guardar se actualizan todas las filas del modelo, igual que Maestros → Modelos.
  - Editar requiere `ficha_modelos → precios`; sin ese permiso es solo lectura. Si el modelo no tiene fila en `preciojf`, aparece "Sin registro" (se crea en Maestros).
  - Después de guardar se vuelve a leer el valor; si la base lo recortó por el tipo de columna, se restaura el anterior y se avisa.
- **Sin cambios en el CSV actual:** la Fase 1 no cambia la emisión. El país y las partidas se usarán desde la Fase 2.

### Fase 2 — CSV nuevo (solo vista previa)

- Función nueva `ctrCsvExportacion($documento)` y modelo propio. **No** reemplaza `ctrGenerarFEFacBolB`.
- Toma una venta `FX` **ya emitida** y genera el CSV en una carpeta de pruebas (no la que vigila eFact).
- Cambios frente al CSV actual:
  - País desde `clientesjf.pais_iso` (no fijo en España).
  - Receptor "sin documento", como hoy.
  - Monto en letras en dólares desde una función propia (no reemplazo de texto).
  - Partida arancelaria solo si existe; si no, vacío (no "No encontrado").
- Comparar columna por columna con el CSV que hoy acepta eFact.

**Prueba:** el CSV nuevo de una factura antigua debe ser igual al actual, salvo los cambios listados.

**Implementado (13-nov-2026):**

- `ControladorExportacion::ctrArmarCsv($doc, "actual"|"nuevo")` arma el CSV **en memoria** con las consultas de solo lectura `mdlFEFacturaCabB` y `mdlFEFacturaDetAExportacion`. No escribe en ninguna carpeta: se descarga desde la pantalla. `ctrCompararCsv` compara celda por celda.
- El modo "actual" sale **idéntico byte por byte** a `ctrGenerarFEFacBolB` (probado con PHP 5.6 y datos simulados).
- Pantalla: Configuración de exportación → pestaña **Archivo eFact (prueba)** (`#archivo`), con la lista de facturas FX, las diferencias, los dos archivos lado a lado y las descargas.
- Hallazgos sobre el CSV actual:
  - **Columna R de los ítems: hoy siempre "No encontrado".** `$codigo_arancel` de `config.php` no llega a la función, porque `config.php` ya está cargado y el `require_once` no lo vuelve a leer. En PHP 8 sería un error fatal.
  - Los valores de `$codigo_arancel` son **códigos de producto SUNAT (catálogo 25, 8 dígitos)**, no partidas de 10. La tabla `exp_partida_lineajf` guarda ese código (precarga: `docs/sql/exportacion-fase2.sql`).
  - Cabecera: alias `h5` duplicado en la fila 6 de `mdlFEFacturaCabB`, así que la provincia del emisor (fila 5 H) sale "-" y la fila 6 H sale vacía.
- Cambios del modo "nuevo":
  - Fila 5 H = LIMA y fila 6 H = "-".
  - País (fila 6 K) desde `clientesjf.pais_iso`.
  - Receptor sin documento (fila 6 A/B).
  - Monto en letras en USD con `montoLetrasUSD`.
  - Columna R con el código SUNAT de la línea, o vacía si no tiene.

### Fase 3 — Emisión desde pedido

**3A implementada (13-nov-2026): reconocimiento y controles en pedidos.** Un pedido es de exportación si el cliente tiene `pais_iso` distinto de Perú; el pedido no lleva marca propia.

- **Control en el servidor:** `ControladorExportacion::ctrValidarFacturarPedido`. Se llama desde `ctrFacturarN` con un bloque aditivo, antes de asignar el correlativo, y solo aplica a factura y boleta (01, 03):
  - Serie FX con un cliente de Perú o sin país: se bloquea.
  - Cliente del exterior sin serie FX: se bloquea.
  - Serie FX con boleta: se bloquea.
  - Serie FX con un pedido que no usa `precio1`: se bloquea.
- **Ayuda en pantalla:** `vistas/js/exportacion-pedidos.js`, con `ajax/exportacion-pedidos.ajax.php` (solo lectura; basta con tener sesión iniciada):
  - En los listados de pedidos, la marca «EXP» con la bandera.
  - En crear o editar pedido, un aviso con el país y la lista de precios.
  - En el modal Facturar, solo series FX para el exterior (se elige sola si hay una) y sin series FX para Perú.
- La emisión sigue usando el flujo actual de `ctrFacturarN`, que ya marca `exportacion = 1` y la moneda USD por la serie FX.

**3B pendiente:** cuenta en USD (`exp_cta_monedajf`) y envío del CSV nuevo a eFact.

- Bandeja: pedidos **preparados por almacén** con lista `precio1` y cliente con país del exterior.
- Validaciones antes de emitir: lista `precio1`, cliente con país distinto de Perú, serie de exportación configurada y pedido completo.
- Al emitir, en una transacción nueva, se llaman las funciones existentes:
  - correlativo atómico de la serie (`talonariosjf`)
  - `ctrRegistrarMovimientos` (stock, almacén principal)
  - `ctrRegistrarVentas` con `exportacion = 1`, `tipo_moneda = 2`
  - `ctrRegistrarCuentaCorriente`
- Tabla nueva **`exp_cta_monedajf`**: `tipo_doc`, `num_cta`, `moneda` (USD), `tipo_cambio` (si se decide), `fecha`. Marca la cuenta como USD sin tocar `cuenta_ctejf`.
- Envío a eFact: CSV de la fase 2, ahora a la carpeta real.

**Prueba:** una factura real de bajo monto; revisar stock, venta, cuenta y respuesta de eFact. Si falla, dar de baja y registrar el intento.

### Fase 4 — Impresión

- Formato nuevo, mismo estilo SAP (`impresion_bolfact_sap.php` como referencia, sin editarlo).
- Muestra el país, el documento real y la dirección del cliente (datos de Clientes), moneda USD, "Exportación – Op. exonerada/inafecta" y monto en letras en dólares.

### Fase 5 — Cobranza en dólares (pendiente de definir)

- Que Cuentas corrientes y CxC muestren estas cuentas como USD (leyendo `exp_cta_monedajf`).
- Definir cómo se registra el pago (en USD, tipo de cambio).

### Fase 6 — Retiro del flujo antiguo

- Cuando las fases 1 a 4 estén probadas: dejar de facturar exportación con la serie FX desde el flujo normal (solo bloquear esa opción, sin borrar código).

## 5. Decisiones (13-nov-2026)

1. **Tipo de cambio:** sí se guarda en `exp_cta_monedajf.tipo_cambio`; puede quedar vacío al emitir y **actualizarse después**.
2. **Cobranza USD:** **se deja como está** por ahora. Pendiente revisar más adelante (Fase 5).
3. **Serie:** **la misma serie FX** y el mismo correlativo de `talonariosjf`.
4. **Partida arancelaria:** tabla lista y vacía; **más adelante alguien puede llenarla**. En la Fase 2 se vio que lo que se envía es el código de producto SUNAT de 8 dígitos; se precargan los 5 de `config.php`.

## 6. Registro de pruebas

| N.º | Fecha | Fase | Documento | Resultado / error de eFact | Cambio aplicado |
|----|-------|------|-----------|----------------------------|-----------------|
| 1 | | | | | |
