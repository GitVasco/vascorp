# Plan: Cuadre de ventas del día

**Estado:** Paso 7 listo (procesar entra a cte). Permisos de prueba: solo ID `6`.  
**Inicio:** 20 ago 2026.  
**No es** Cancelar abonos. Al **procesar**, sí escribe en cuenta corriente.

Pantalla: **Cuadre de ventas del día** · Ruta: `cuadre-ventas` · Menú: Cuentas corrientes.

---

## Qué buscamos

Cada persona arma **su propio cuadre**: ve **sus ventas** (las que registró al facturar) de **un día**, marca **boletas y/o facturas de un solo cliente** y registra **cómo se pagaron**. No arma el cuadre de otro.

Un cliente puede tener muchos documentos. Puede pagarlos con:

- **un solo abono** (una OP cubre varios documentos), o
- **varios pagos combinados** (Yape + efectivo + depósito, etc., que suman el total).

Se permite **pago parcial** de un documento (queda saldo).

Eso **no entra a cuenta corriente**. Quien arma **solo registra**. **Cuentas corrientes** confirma o rechaza. Recién después, **otras personas** (no quien validó) pulsan el botón y el cuadre **entra a cuentas**.

Si el medio trae OP: al registrar se **busca en Abonos** (`abonosjf.num_ope`). Si existe, se amarra y reserva. Si no está, igual se registra la OP.

---

## Qué hay hoy (y por qué no sirve)

| Pieza | Qué hace | Por qué no alcanza |
|-------|----------|-------------------|
| **Abonos** (`/abonos`) | Entra el dinero del banco: fecha, monto, agencia, **OP** (`num_ope`) | Solo es el depósito. No dice a qué documentos aplica. |
| **Cancelar abonos** (`/cancelar-abonos`) | 1 abono → 1 cuenta. **Inserta ya** en `cuenta_ctejf` (`tip_mov = '-'`) y borra o recorta el abono | 1 a 1, sin validación de un segundo usuario, y toca cte al instante. |

El módulo nuevo es **aparte**. No se pisa Cancelar abonos. No se reutiliza ese flujo.

---

## Flujo objetivo

```text
1. Abonos                 → entra la OP (dinero en banco)
2. Quien facturó          → ve SUS ventas del día
                          → elige documentos de UN cliente
                          → registra el medio (efectivo / Yape / depósito / tarjeta / link / Culqi)
                          → si hay OP, la busca en Abonos (si está, reserva; si no, igual registra)
                          → queda REGISTRADO (no toca cte)
3. Cuentas corrientes     → confirma o rechaza
4. Otras personas         → botón: entra a cuenta_ctejf
```

Estados de un lote:

`BORRADOR` → `REGISTRADO` → `VALIDADO` → `PROCESADO`  
(también `ANULADO` / `RECHAZADO`)

Hasta `PROCESADO`, **cero** `INSERT`/`UPDATE`/`DELETE` sobre `cuenta_ctejf`.

---

## Reglas de negocio (acordadas)

1. Quien registra ve **solo sus ventas**: `cuenta_ctejf.usuario` = su ID de sesión. Elige el **día**. No elige a otra persona.
2. Documentos del día: **boletas (`03`) y facturas (`01`)**, cargos `tip_mov = '+'`.
3. Un lote es de **un solo cliente**. Varios documentos de ese cliente.
4. Los pagos deben cuadrar: `suma(monto a aplicar de docs) = suma(pagos)`.
5. Medios (código de pago): **efectivo 80**, **Yape 15**, **depósito 05**, **tarjeta 17**, **link de pago 16**, **Culqi 14**. Se pueden combinar en el mismo lote.
6. Se elige el **medio**. La **OP es opcional** (efectivo no lleva). Si hay OP, se busca en Abonos: si está, se reserva; si no está, igual se registra.
7. Una OP **en Abonos** solo un lote activo a la vez. Si el lote usa menos, el sobrante queda en el abono. Mientras tanto no se usa en otro lote.
8. Pago parcial de un documento: sí. `monto_aplicar` ≤ saldo. El resto sigue pendiente.
9. Al registrar, la OP queda **reservada** (no se borra el abono). No se toca cte.
10. Quien arma **solo registra**. No confirma. Cuentas corrientes confirma o rechaza.
11. El botón **entrar a cuentas** lo pulsan **otras personas** (no quien validó). Fase posterior.
12. Cancelar abonos sigue igual para el resto de casos. Una OP reservada aquí no se usa allá.

---

## Modelo de datos (propuesto)

Tablas nuevas. Convención `*jf`.

### Cabecera — `cuadre_ventasjf`

Un lote = un usuario de ventas + un día + un cliente + docs + pagos.

| Campo | Uso |
|-------|-----|
| `id` | PK |
| `fecha_ventas` | Día de las ventas |
| `usuario_ventas` | Quien registró esas ventas (`cuenta_ctejf.usuario`) |
| `cliente` | Un solo cliente por lote |
| `estado` | `BORRADOR` / `REGISTRADO` / `VALIDADO` / `PROCESADO` / `ANULADO` / `RECHAZADO` |
| `total_docs` / `total_pagos` | Para cuadrar |
| `usuario_registro` / `fecha_registro` | Quién armó |
| `usuario_validacion` / `fecha_validacion` | Quién validó |
| `usuario_proceso` / `fecha_proceso` | Quién mandó a cte (fase posterior) |
| `observacion` | Texto libre |

### Documentos del lote — `cuadre_ventas_docjf`

| Campo | Uso |
|-------|-----|
| `id_cuadre` | Lote |
| `id_cuenta` | Cargo en `cuenta_ctejf` (solo lectura hasta procesar) |
| `tipo_doc` / `num_cta` / `cliente` | Copia de referencia |
| `monto_doc` | Total del documento |
| `monto_aplicar` | Cuánto se cancela en este lote (puede ser parcial) |

### Pagos del lote — `cuadre_ventas_medjf`

| Campo | Uso |
|-------|-----|
| `id_cuadre` | Lote |
| `tipo_medio` | `cod_pago`: 80 / 15 / 05 / 17 / 16 / 14 (legado: EFECTIVO, YAPE, ABONO_OP) |
| `id_abono` | `abonosjf.id` si la OP estaba en Abonos |
| `num_ope` | OP del medio (opcional; efectivo no lleva) |
| `monto` | Cuánto de ese medio se usa en este lote |

### Reserva en Abonos

No borrar el abono al registrar. Marcar reserva (p. ej. `id_cuadre` en `abonosjf` o tabla puente). Si el lote se anula o rechaza, se libera.

Al **procesar** (fase posterior): escribir en `cuenta_ctejf`, bajar saldos, recortar o eliminar el abono según el monto usado.

---

## Permisos

Sector: `gestion_comercial`. Clave: `cuadre_ventas`.  
IDs: se piden al empezar el Paso 1. No se inventan.

| Rol | Acciones | Qué ve / qué hace |
|-----|----------|-------------------|
| Quien facturó | `ver` + `registrar` | Entra, ve **las ventas que él registró** (`cuenta_ctejf.usuario` = su ID). Arma su cuadre. No confirma. |
| Cuentas corrientes | `ver` + `validar` | Ve los cuadres **REGISTRADO**, confirma o rechaza. |
| Otros (entrar a cuentas) | `ver` + `procesar` | Botón a cte (Paso 7). |

Pruebas ahora: **solo ID `6`** en las cuatro acciones. No se agregan más IDs hasta que lo pidan.

Quién puede cuadrar no es una lista de cajeros: es el **usuario grabado en cada venta**. Si la venta la registró el 45, solo el 45 la ve en su cuadre (cuando tenga `ver`).

---

## Archivos previstos (cuando se implemente)

| Rol | Path |
|-----|------|
| Vista | `vistas/modulos/cuentas-corrientes/cuadre-ventas.php` |
| JS / CSS | `vistas/js/cuadre-ventas.js`, `vistas/css/cuadre-ventas.css` |
| AJAX | `ajax/cuadre-ventas.ajax.php` |
| Controlador / modelo | `controladores/cuadre-ventas.controlador.php`, `modelos/cuadre-ventas.modelo.php` |
| SQL | `docs/sql/cuadre-ventas.sql` |
| Permisos | `controladores/permisos-modulos.json` → `gestion_comercial.cuadre_ventas` |
| Menú / ruta | `vistas/modulos/menu.php`, `vistas/plantilla.php` |

Módulo acotado. No se edita la lógica de Cancelar abonos ni de `/ver-cuentas` para este trabajo.

---

## Fuera de alcance ahora

- Reemplazar Cancelar abonos.
- Sync VascoPro / rendición de campo.
- Importar extracto bancario (eso sigue en Abonos).
- Adjuntos de voucher (salvo que lo pidan después).

---

## Pasos (validar uno a uno)

### Paso 0 — Acuerdos

- [x] Nombre / ruta: Cuadre de ventas del día · `cuadre-ventas`
- [x] Roles: cada uno arma el suyo (`registrar`); Cuentas corrientes confirma (`validar`); **otros** entran a cuentas (`procesar`)
- [x] IDs de prueba: solo `6` (ver / registrar / validar / procesar)
- [x] Fuente: el usuario **ya está en la venta** (`cuenta_ctejf.usuario`). El cuadre filtra por el ID de quien está logueado.
- [x] Documentos: boletas (`03`) y facturas (`01`)
- [x] Medios: OP + efectivo + Yape
- [x] Pago parcial: sí
- [x] OP: un solo lote activo; el sobrante queda para después
- [x] Un lote = un cliente

### Paso 1 — Cascarón vacío

- [x] Permisos + menú + ruta + vista en blanco (“Cuadre de ventas del día”).
- [x] SQL de las tres tablas + reserva en Abonos **sin** lógica de cte (`docs/sql/cuadre-ventas.sql`). **Ejecutar en BD.**
- [x] Quien no tiene `ver` no entra. Pruebas: solo ID `6`.

**Validar:** entra el `6`. Otro usuario no ve el menú. Tras ejecutar el SQL, la pantalla dice “Tablas listas”. `cuenta_ctejf` intacta.

### Paso 2 — Listar ventas del día

- [x] Filtro: fecha (queda en la URL). Las ventas son **siempre las suyas** (`cuenta_ctejf.usuario` = sesión), salvo **ID 6 en pruebas**, que ve **todas** las del día.
- [x] Solo vendedores que **empiezan con `08`**.
- [x] Solo documentos **PENDIENTE**.
- [x] Grilla: tipo (boleta/factura), documento, cliente, monto, saldo, estado.
- [x] Solo lectura. Nada se guarda. No escribe en cte.

**Validar:** con un día real, salen **mis** boletas y facturas. No salen las de otro. Si cambio la fecha, cambia la lista.

### Paso 3 — Armar lote (documentos)

- [x] Marcar varios documentos del **mismo cliente**.
- [x] Permitir monto parcial por documento.
- [x] Ver total a cancelar.
- [x] Guardar `BORRADOR` (cabecera + docs). Aún sin pagos. No escribe en cte.

**Validar:** elijo 3 documentos de un cliente (uno parcial), guardo, salgo y al volver siguen. No puedo mezclar otro cliente.

### Paso 4 — Amarrar pagos / OP

- [x] Medios: efectivo, Yape, depósito, tarjeta, link, Culqi. Combinables.
- [x] Se elige el medio. OP opcional (excepto efectivo, que no lleva). Si está en Abonos, se reserva; si no, igual se registra.
- [x] OP en Abonos: monto alcanza, no está en otro lote activo.
- [x] Un pago → varios docs, o varios pagos → el total (incl. parciales).
- [x] Cuadre obligatorio para pasar a `REGISTRADO`.
- [x] La OP queda reservada entera para ese lote (sobrante no se usa en paralelo). No se borra el abono. No se toca cte.

**Validar (casos):**

1. Una OP de 300 cubre tres docs de 100 → registra, OP reservada, cte igual.
2. OP 200 + efectivo 50 + Yape 50 cubren 300 → registra.
3. Pago parcial: boleta 200, aplico 80 con OP → registra, saldo conceptual 120 (aún no en cte).
4. OP inexistente en Abonos → igual registra (queda sin reserva).
5. OP ya en otro lote activo → error.
6. Suma de pagos ≠ suma aplicada → no pasa a `REGISTRADO`.
7. En Abonos / Cancelar abonos esa OP no está disponible.

### Paso 5 — Validación de otro usuario

- [x] Lista de lotes `REGISTRADO`.
- [x] Validar / rechazar (con motivo).
- [x] El que registró no valida el suyo.
- [x] Rechazado o anulado **libera** la OP.
- [x] Quien registró puede **cancelar** un lote por validar si se equivocó.

**Validar:** el cajero A registra, Cuentas corrientes confirma. A no puede confirmar el suyo. Tras rechazar, la OP vuelve a estar libre. Cuentas corrientes no pulsa el botón a cte.

### Paso 6 — Gancho a cte (sin ejecutar)

- [x] Estado `VALIDADO` deja el lote listo.
- [x] Permiso `procesar` y botón **Procesar a cuenta corriente** visible pero **fuera de servicio**.
- [x] Comentario en código / SQL: qué hará el botón, **sin** ejecutarlo.

**Validar:** un lote validado no cambió saldos en `/ver-cuentas`. El botón avisa que aún no entra a cuentas.

### Paso 7 — Procesar a cte

- [x] Recorre docs + pagos del lote `VALIDADO`.
- [x] Escribe movimientos `-` en `cuenta_ctejf` (`cod_pago` del medio; `notas` `OP-…` si hay OP).
- [x] Actualiza saldo/estado de cada cargo (parcial o cancelado).
- [x] Consume el abono (baja el monto usado; si queda, el sobrante sigue en Abonos).
- [x] Pasa el lote a `PROCESADO`. Idempotente. Quien validó no procesa (salvo ID de prueba).

**Validar:** los docs quedan con el saldo correcto en cte, la OP usada ya no está (o quedó el sobrante), el lote no se reprocesa.

---

## Decisiones Paso 0

| Tema | Decisión |
|------|----------|
| Nombre / ruta | **Cuadre de ventas del día** · `cuadre-ventas` |
| Quién registra | Cada uno arma **su** cuadre. El filtro es el usuario **de la venta**, no una lista de cajeros. |
| Quién valida | Cuentas corrientes confirma. Pruebas: ID `6`. |
| Quién entra a cuentas | Otros (`procesar`). Pruebas: ID `6`. |
| Fuente de ventas | `cuenta_ctejf` cargos `+`, **solo las del usuario logueado** + fecha |
| Tipos de documento | Boletas (`03`) y facturas (`01`) |
| Medios de pago | OP de Abonos + efectivo + Yape (combinables) |
| Pago parcial | Sí |
| OP partida en varios lotes | No en paralelo. Un lote activo. Sobrante queda en el abono para después. |
| ¿Un lote = un cliente? | Sí |

---

## Relación con Cancelar abonos

Siguen conviviendo:

- **Cancelar abonos:** casos 1 a 1 que ya se aplican ya a cte.
- **Este módulo:** ventas del día, N documentos ↔ M pagos, con validación.

Al reservar una OP aquí, esa OP **no** debe poder usarse en Cancelar abonos. Eso se cubre en el Paso 4.
