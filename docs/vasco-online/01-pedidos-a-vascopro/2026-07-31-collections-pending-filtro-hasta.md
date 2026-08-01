# Pedido a VascoPro: filtro `until` (hasta) en GET cobranzas pendientes

**Fecha:** 2026-07-31  
**Origen:** vascorp — pantalla `/rendicion-vasco-caja`  
**Prioridad:** media (mejora operativa de caja; no bloquea confirmar/anular)

---

## Problema

En caja usamos `GET /v2/sync/collections-pending-delivery` para listar cobranzas en **`pending_delivery`** antes de confirmar o anular.

Hoy el GET documenta solo **`since`** (fecha mínima). En vascorp la pantalla tiene filtro **Desde**, pero **no hay forma confiable de acotar por fecha máxima** (Hasta).

Casos de uso reales en caja:

- Rendir solo lo cobrado **entre el 1 y el 15** de un mes.
- Revisar pendientes **hasta ayer** (excluir cobros de hoy).
- Cuadrar cierre semanal sin mezclar cobranzas de la semana siguiente.

Sin un tope de fecha en el API, vascorp solo puede:

1. Enviar `since` y confiar en `limit` (máx. 500) — incompleto si hay más pendientes.
2. Filtrar **Hasta** en el front sobre los ítems devueltos — **incorrecto** si la lista está truncada por `limit`.

Necesitamos un parámetro de **fecha máxima** en el GET, simétrico a `since`.

---

## Qué necesitamos

### 1. Parámetro nuevo en GET existente

Extender el endpoint ya usado:

```http
GET /v2/sync/collections-pending-delivery
Authorization: {API_KEY}
```

Parámetro propuesto:

| Parámetro | Tipo | Obligatorio | Descripción |
|-----------|------|-------------|-------------|
| **`until`** | `YYYY-MM-DD` | No | Fecha máxima inclusive del cobro (`created_at` o campo que hoy filtra `since`) |

**Alternativa de nombre:** si en Vasco ya usan otro convención (`to`, `before`, `date_to`), indicarlo en la respuesta; vascorp se adapta al nombre final.

### 2. Semántica esperada

| Combinación | Comportamiento |
|-------------|----------------|
| Sin fechas | Sin filtro por fecha (como hoy) |
| Solo `since` | Cobranzas con fecha **≥ since** (00:00:00 del día, timezone acordado) |
| Solo `until` | Cobranzas con fecha **≤ until** (23:59:59 del día, timezone acordado) |
| `since` + `until` | Rango cerrado **[since, until]** inclusive |
| `since` > `until` | **400** con mensaje claro (parámetros inválidos) |

**Campo de fecha:** el mismo que ya usa `since` (presumimos `created_at` de la cobranza). Confirmar en la respuesta.

**Timezone:** mismo criterio que `since` (idealmente `America/Lima` o el que ya aplique Vasco para sync).

### 3. Ejemplos

Pendientes de julio 2026 (rango completo):

```http
GET /v2/sync/collections-pending-delivery?status=pending_delivery&since=2026-07-01&until=2026-07-31&limit=500
Authorization: {API_KEY}
```

Pendientes hasta ayer (sin mínimo):

```http
GET /v2/sync/collections-pending-delivery?status=pending_delivery&until=2026-07-30&limit=100
Authorization: {API_KEY}
```

Combinado con vendedor (si ya existe `seller_username` / `seller_user_id`):

```http
GET /v2/sync/collections-pending-delivery?status=pending_delivery&seller_username=20123456789&since=2026-07-01&until=2026-07-15&limit=500
Authorization: {API_KEY}
```

### 4. Respuesta

Sin cambios en el shape actual de `results` (`items`, `count`, `total_amount`, `trace_id`, etc.).

Opcional pero útil: reflejar en la respuesta los filtros aplicados, por ejemplo:

```json
{
  "results": {
    "status": "pending_delivery",
    "since": "2026-07-01",
    "until": "2026-07-31",
    "count": 12,
    "total_amount": 1840.50,
    "items": [ "..."]
  }
}
```

### 5. Validación y errores

| Caso | HTTP | Notas |
|------|------|-------|
| `until` con formato inválido | 400 | Ej. `31/07/2026` o `2026-13-01` |
| `since` > `until` | 400 | Mensaje explícito |
| `until` futuro | A definir | Aceptable permitirlo; vascorp no lo usará por defecto |
| Parámetro desconocido | Ignorar o 400 | Igual política que el resto del API v2 |

---

## Fuera de alcance (por ahora)

- Paginación con `offset` / cursor (vascorp sigue con `limit` ≤ 500).
- Cambios en POST deliver o cancel.
- Filtro por hora (solo fecha `YYYY-MM-DD`).
- Nuevo endpoint; preferimos extender el GET actual.

---

## Criterios de aceptación

- [ ] GET acepta `until` en formato `YYYY-MM-DD`
- [ ] Con `since` + `until`, solo devuelve cobranzas dentro del rango inclusive
- [ ] Con solo `until`, devuelve cobranzas hasta esa fecha inclusive
- [ ] `since` > `until` → 400 con mensaje claro
- [ ] Compatible con filtros existentes (`status`, `seller_username`, `limit`, `trace_id`)
- [ ] `count` y `total_amount` coherentes con el rango filtrado
- [ ] Documento/contrato HTTP actualizado (Postman o markdown en repo Vasco)

---

## Impacto en vascorp (después de la respuesta)

Cuando exista el parámetro, vascorp implementará:

1. Campo **Hasta** en `/rendicion-vasco-caja` (junto al **Desde** ya existente).
2. Reenvío en `ajax/cuentas-corrientes/vasco-cobranzas.ajax.php` → `ControladorVascoSync::ctrListarCobranzasPendientes`.
3. Validación local: si Desde > Hasta, aviso antes de consultar.

Archivos afectados (referencia interna):

- `vistas/modulos/cuentas-corrientes/rendicion-vasco-caja.php`
- `vistas/js/vasco-cobranzas-caja.js`
- `ajax/cuentas-corrientes/vasco-cobranzas.ajax.php`
- `controladores/vasco-sync.controlador.php`

---

## Contexto actual (ya existe)

| Parámetro GET | Estado |
|---------------|--------|
| `status` | Existe (`pending_delivery`, `delivered`, …) |
| `since` | Existe — fecha mínima |
| `until` | **No existe** — este pedido |
| `seller_username` | Existe (documentado) |
| `limit` | Existe (máx. 500) |
| `trace_id` | Existe |

Guías vigentes:

- [`../03-docs-existentes/cobranzas/VASCOPRO_SYNC_RENDICION_COBRANZAS.md`](../03-docs-existentes/cobranzas/VASCOPRO_SYNC_RENDICION_COBRANZAS.md)
- [`../03-docs-existentes/implementacion-vascorp/VASCORP_IMPLEMENTAR_RENDICION_COBRANZAS.md`](../03-docs-existentes/implementacion-vascorp/VASCORP_IMPLEMENTAR_RENDICION_COBRANZAS.md)

---

## Respuesta esperada de VascoPro

Pegar contrato final en:  
[`../02-respuestas-vascopro/`](../02-respuestas-vascopro/)  
(ej. `2026-07-31-collections-pending-until.md`)

Incluir: nombre final del parámetro, semántica de timezone, ejemplos GET, códigos HTTP y confirmación del campo de fecha filtrado.
