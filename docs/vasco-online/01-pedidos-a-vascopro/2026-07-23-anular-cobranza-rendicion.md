# Pedido a VascoPro: anular / cancelar cobranza desde rendición (caja)

**Fecha:** 2026-07-23  
**Origen:** vascorp — pantalla `/rendicion-vasco-caja`  
**Prioridad:** alta (bloquea cierre limpio de caja cuando hay cobro erróneo)

---

## Problema

Hoy vascorp solo puede **confirmar** cobranzas pendientes (`POST /v2/sync/collections-deliver` → `delivered`).

Si el vendedor registró un cobro por error y caja se da cuenta al rendir:

- No hay forma de **rechazar / anular** desde vascorp.
- No confirmar deja el ítem en `pending_delivery` (sigue apareciendo como pendiente).
- La corrección hoy depende de anular a mano en admin Vasco.

Necesitamos que caja pueda **anular desde vascorp** y que Vasco refleje el estado correcto.

---

## Qué necesitamos

### 1. Estado

Confirmar (o habilitar) un estado terminal tipo **`cancelled`** (o `annulled`) para cobranzas que estaban en `pending_delivery`.

Reglas esperadas:

| Desde | Acción | Resultado |
|-------|--------|-----------|
| `pending_delivery` | Anular | `cancelled` |
| `delivered` | Anular | **No permitido** (o error claro) |
| `cancelled` | Anular de nuevo | Idempotente (`already_cancelled`) |

La cobranza anulada **no debe volver** en `GET /v2/sync/collections-pending-delivery`.

### 2. Endpoint sync (ERP)

Algo equivalente a:

```http
POST /v2/sync/collections-cancel
Authorization: {API_KEY}
Content-Type: application/json
```

Body propuesto (ajustar al estilo de `collections-deliver`):

```json
{
  "trace_id": "vascorp-cancel-20260723-001",
  "cancelled_by": "caja.vascorp",
  "items": [
    {
      "code": "COB-2026-0142",
      "reason": "Monto incorrecto registrado por vendedor"
    }
  ]
}
```

Mínimo por ítem:

| Campo | Obligatorio | Notas |
|-------|-------------|--------|
| `code` o `id` | Sí (al menos uno) | Igual que deliver |
| `reason` | Sí | Motivo visible en auditoría (máx. razonable, ej. 255) |
| `cancelled_by` | Sí (header o body) | Usuario/proceso ERP |
| `trace_id` | Recomendado | Correlación con logs vascorp |

### 3. Respuesta

Misma filosofía que deliver:

- `200` / `207` con `cancelled`, `already_cancelled`, `failed[]`
- Errores claros: no encontrada, no está pendiente, ya entregada, sin motivo

### 4. Auditoría en Vasco

Registrar en `collection_audits` (o equivalente):

- quién anuló (`cancelled_by`)
- cuándo
- motivo (`reason`)
- `trace_id` si aplica

Visible para vendedor/admin en historial de cobranzas del cliente (ej. “Anulada / Cancelada”).

---

## Fuera de alcance (por ahora)

- No pedimos que Vasco impute ni revierta documentos en ERP.
- No pedimos anular cobranzas ya `delivered` desde este endpoint.
- No pedimos UI nueva en Vasco admin (si ya pueden anular allá, OK; el pedido es el **sync para ERP**).

---

## Criterios de aceptación

- [ ] POST cancela una cobranza `pending_delivery` → estado `cancelled`
- [ ] Deja de aparecer en GET pendientes
- [ ] Segundo POST del mismo `code` → `already_cancelled` (no error duro)
- [ ] Intentar cancelar una `delivered` → falla con mensaje claro
- [ ] `reason` + `cancelled_by` quedan auditados
- [ ] Documento/contrato HTTP (Postman o markdown) para que vascorp implemente el botón **Rechazar / Anular** en caja

---

## Contexto actual (ya existe)

| Endpoint | Uso |
|----------|-----|
| `GET /v2/sync/collections-pending-delivery` | Listar pendientes |
| `POST /v2/sync/collections-deliver` | Confirmar recepción en empresa |

Guía vigente: [`../03-docs-existentes/cobranzas/VASCOPRO_SYNC_RENDICION_COBRANZAS.md`](../03-docs-existentes/cobranzas/VASCOPRO_SYNC_RENDICION_COBRANZAS.md)

---

## Respuesta esperada de VascoPro

Pegar contrato final en:  
[`../02-respuestas-vascopro/`](../02-respuestas-vascopro/)  
(ej. `2026-07-23-collections-cancel.md`)

Incluir: ruta exacta, payload, códigos HTTP, estados válidos y ejemplo de respuesta.

---

## Respuesta VascoPro

→ [`../02-respuestas-vascopro/2026-07-23-collections-cancel.md`](../02-respuestas-vascopro/2026-07-23-collections-cancel.md)
