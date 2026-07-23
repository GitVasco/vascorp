# Respuesta VascoPro: anular cobranza desde rendición (caja)

**Fecha:** 2026-07-23  
**Pedido:** [`../01-pedidos-a-vascopro/2026-07-23-anular-cobranza-rendicion.md`](../01-pedidos-a-vascopro/2026-07-23-anular-cobranza-rendicion.md)  
**Estado:** implementado en Vasco

Contrato HTTP completo (repo Vasco): `postman/VASCORP_SYNC_COLLECTIONS_DELIVER.md` §3  
Plantilla JSON: `postman/samples/vascorp-collections-cancel.json`

---

## Resumen

Quedó disponible:

```http
POST /v2/sync/collections-cancel
```

Anula cobranzas en **`pending_delivery`** → **`cancelled`**.  
No anula `delivered`. Idempotente con `already_cancelled`.

El estado `cancelled` ya existía en el modelo Vasco; este endpoint completa la transición desde el sync de caja.

---

## Conexión

| Entorno | Base URL |
|---------|----------|
| Desarrollo | `http://api.vasco.io:8084` |
| Producción | `https://api.vasco.io` (ajustar según despliegue) |

### Headers

```
Authorization: {API_KEY}
Content-Type: application/json
```

- Misma `API_KEY` que deliver / customers-bulk (sin `Bearer`).

---

## Uso del endpoint

### Request

```http
POST /v2/sync/collections-cancel
Authorization: {API_KEY}
Content-Type: application/json
```

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

### Campos body

| Campo | Obligatorio | Descripción |
|-------|-------------|-------------|
| `trace_id` | No | Correlación logs (si falta, Vasco genera uno) |
| `cancelled_by` | No* | Usuario/proceso ERP. Default `vascorp_api`. Máx. 80 |
| `items` | Sí | 1–500 cobranzas |

\* En pantalla de caja **siempre** enviar el usuario operador.

### Por ítem

| Campo | Obligatorio | Descripción |
|-------|-------------|-------------|
| `code` o `id` | Sí (al menos uno) | Igual que deliver (`COB-YYYY-####` o id numérico) |
| `reason` | **Sí** | Motivo visible en auditoría. Máx. **255** |
| `cancelled_by` | No | Sobrescribe el de raíz |

---

## Respuestas

### 200 — todo OK

```json
{
  "status": 200,
  "results": {
    "ok": true,
    "trace_id": "vascorp-cancel-20260723-001",
    "processed": 1,
    "cancelled": 1,
    "already_cancelled": 0,
    "items": [
      {
        "index": 0,
        "id": 42,
        "code": "COB-2026-0142",
        "status": "cancelled"
      }
    ],
    "failed": []
  }
}
```

### 207 — parcial

Algunos ítems OK, otros en `failed[]`. Reintentar solo fallidos.

### Idempotencia

Segundo POST del mismo `code` ya anulado → ítem con `status: "already_cancelled"` (no error).

### Errores por ítem (`failed[]`)

| Mensaje (aprox.) | Cuándo |
|------------------|--------|
| `reason es obligatorio…` | Falta motivo |
| `Cobranza no encontrada.` | `code`/`id` inválido |
| `ya fue entregada a empresa; no se puede anular…` | Estado `delivered` |
| `no está pendiente de entrega…` | Otro estado |

---

## Transiciones

| Desde | Acción | Resultado |
|-------|--------|-----------|
| `pending_delivery` | Cancel | `cancelled` |
| `delivered` | Cancel | `failed[]` |
| `cancelled` | Cancel de nuevo | `already_cancelled` |

Tras anular, **deja de aparecer** en:

```http
GET /v2/sync/collections-pending-delivery?status=pending_delivery
```

---

## Flujo en pantalla `/rendicion-vasco-caja`

```
1. GET  /v2/sync/collections-pending-delivery?...
2. Operador revisa grilla
3a. Confirmar → POST /v2/sync/collections-deliver
3b. Rechazar / Anular → pedir motivo → POST /v2/sync/collections-cancel
4. Refrescar GET (el ítem anulado ya no sale)
```

### Ejemplo PHP (caja)

```php
$traceId = 'vascorp-cancel-' . date('Ymd-His');

$result = postToVasco('/v2/sync/collections-cancel', [
    'trace_id' => $traceId,
    'cancelled_by' => $usuarioCaja, // ej. "caja.vascorp"
    'items' => [
        [
            'code' => $cobCode, // "COB-2026-0142"
            'reason' => $motivo, // obligatorio, máx. 255
        ],
    ],
]);

$results = $result['results'] ?? [];
foreach ($results['items'] ?? [] as $item) {
    // status: cancelled | already_cancelled
}
foreach ($results['failed'] ?? [] as $fail) {
    // mostrar $fail['error'] al operador
}
```

### curl (dev)

```bash
curl -sS -X POST 'http://api.vasco.io:8084/v2/sync/collections-cancel' \
  -H "Authorization: ${API_KEY}" \
  -H 'Content-Type: application/json' \
  -d '{
    "trace_id": "vascorp-cancel-20260723-001",
    "cancelled_by": "caja.vascorp",
    "items": [
      {
        "code": "COB-2026-0142",
        "reason": "Monto incorrecto registrado por vendedor"
      }
    ]
  }'
```

---

## Auditoría en Vasco

Cada anulación exitosa inserta en `collection_audits`:

| Campo | Valor |
|-------|--------|
| `action` | `cancelled` |
| `old_values` | `{ "status_collection": "pending_delivery" }` |
| `new_values` | `{ "status_collection": "cancelled" }` |
| `metadata` | `{ "source": "vascorp_api", "cancelled_by": "...", "reason": "..." }` |
| `trace_id` | el del request |

En admin Vasco el historial del cliente muestra estado **Anulada**.

---

## Fuera de alcance (confirmado)

- No se imputa ni se revierte nada en el ERP desde Vasco.
- No se anulan cobranzas ya `delivered` por este endpoint.
- No hay UI nueva de anulación en admin Vasco en este entregable (el sync es el pedido).

---

## Criterios de aceptación

- [x] POST cancela `pending_delivery` → `cancelled`
- [x] Deja de aparecer en GET pendientes
- [x] Segundo POST → `already_cancelled`
- [x] Cancel de `delivered` → `failed[]` con mensaje claro
- [x] `reason` + `cancelled_by` auditados
- [x] Contrato HTTP documentado (este archivo + Postman en Vasco)

---

## Endpoints sync cobranzas (mapa actual)

| Método | Ruta | Uso |
|--------|------|-----|
| GET | `/v2/sync/collections-pending-delivery` | Listar pendientes |
| POST | `/v2/sync/collections-deliver` | Confirmar recepción |
| POST | `/v2/sync/collections-cancel` | **Anular / rechazar en caja** |
