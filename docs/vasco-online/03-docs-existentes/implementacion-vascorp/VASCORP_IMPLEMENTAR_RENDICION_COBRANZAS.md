# vascorp — implementar rendición de cobranzas (handoff)

Documento para llevar al proyecto **vascorp/vascopro** y cerrar la integración de **validación de efectivo cobrado en campo**.

Guía extendida: [`VASCOPRO_SYNC_RENDICION_COBRANZAS.md`](../cobranzas/VASCOPRO_SYNC_RENDICION_COBRANZAS.md)  
Contrato HTTP detallado: docs VascoPro (`postman/VASCORP_SYNC_COLLECTIONS_DELIVER.md`).

---

## Atención — medios de pago (no confundir montos)

Vasco permite cobrar con **efectivo + billetera + transferencia** en una misma cobranza.

| Campo GET | Qué es | En caja / rendición |
|-----------|--------|---------------------|
| **`amount`** | Solo **efectivo** en custodia del vendedor | **Usar este valor** |
| **`amount_total`** | Total cobrado al cliente (todos los medios) | Informativo; no es el monto a recibir en caja |

- Mixto S/ 100 (60 cash + 40 Yape) → en pendientes sale con `amount: 60`, `amount_total: 100`.
- Solo Yape/transfer → **no sale** en este GET (nada que rendir en efectivo).
- Detalle: sección **0** de [`VASCOPRO_SYNC_RENDICION_COBRANZAS.md`](../cobranzas/VASCOPRO_SYNC_RENDICION_COBRANZAS.md).

Pantalla implementada: `/rendicion-vasco-caja`.

---

## Checklist (definition of done)

- [x] Job o pantalla de caja consulta `GET /v2/sync/collections-pending-delivery`
- [x] Filtros usados según negocio (`seller_username`, `since`, `limit` ≤ 500)
- [x] Por cada cobranza confirmada en ERP: `POST /v2/sync/collections-deliver` con `code` o `id`
- [x] Por cada cobranza rechazada en caja: `POST /v2/sync/collections-cancel` con `code`/`id` + `reason`
- [x] Se envía `delivered_by` (usuario/proceso) y opcional `external_reference` (nº rendición ERP)
- [x] Se envía `cancelled_by` + `reason` en anulación
- [x] Manejo de respuesta **207** (`failed[]`) y reintento selectivo
- [x] Idempotencia: `already_delivered` / `already_cancelled` no se tratan como error
- [x] Log de `trace_id` por corrida
- [ ] Verificación en Vasco admin → visita cliente → cobranza **Entregado a empresa** o **Anulada**
- [x] UI/caja muestra **Efectivo** (`amount`) y **Otros medios** (`amount_total − amount`, billetera/transfer)

---

## 1. Prerrequisito

Cobranzas registradas por vendedores en **admin Vasco** (visita → Cobrar). Sin eso, el GET devuelve lista vacía.

Maestro de clientes ya sincronizado ayuda a cruzar `customer.external_id` con el ERP.

---

## 2. Qué construir en vascorp

### Opción A — Job automático

```
CollectionDeliverySyncJob
├── trace_id por corrida
├── GET pendientes (limit 500, paginar si hace falta)
├── por cada ítem: registrar rendición en ERP
├── POST deliver (batch de ítems confirmados)
└── log processed / failed / trace_id
```

### Opción B — Pantalla de caja (implementada)

```
Pantalla rendición (/rendicion-vasco-caja)
├── GET con seller_username o rango de fechas (since)
├── grilla: código COB, cliente, vendedor, efectivo (amount), otros medios (amount_total − amount), fecha
├── botón Confirmar → POST deliver (un ítem o batch)
├── botón Rechazar / Anular → pedir motivo → POST cancel
└── mostrar resultado (delivered / cancelled / already_* / error)
```

---

## 3. HTTP

| Item | Valor |
|------|-------|
| URL dev GET | `http://api.vasco.io:8084/v2/sync/collections-pending-delivery` |
| URL dev POST deliver | `http://api.vasco.io:8084/v2/sync/collections-deliver` |
| URL dev POST cancel | `http://api.vasco.io:8084/v2/sync/collections-cancel` |
| URL prod | `https://api.vasco.io/v2/sync/...` |
| Auth | `Authorization: {API_KEY}` |
| GET Content-Type | — |
| POST Content-Type | `application/json` |

### Ejemplo GET

```
GET /v2/sync/collections-pending-delivery?status=pending_delivery&limit=100&since=2026-06-01
Authorization: {API_KEY}
```

### Ejemplo POST deliver

```json
{
  "trace_id": "vascorp-deliver-20260616-001",
  "delivered_by": "usuario.caja",
  "items": [
    {
      "code": "COB-2026-0142",
      "external_reference": "REND-2026-00088"
    }
  ]
}
```

### Ejemplo POST cancel

```json
{
  "trace_id": "vascorp-cancel-20260723-001",
  "cancelled_by": "usuario.caja",
  "items": [
    {
      "code": "COB-2026-0142",
      "reason": "Monto incorrecto registrado por vendedor"
    }
  ]
}
```

---

## 4. Pseudocódigo mínimo

```php
function syncCollectionDeliveries(): void
{
    $traceId = 'vascorp-deliver-' . date('Ymd-His');
    $items = vascoGet('/v2/sync/collections-pending-delivery', [
        'status' => 'pending_delivery',
        'limit' => 500,
        'trace_id' => $traceId,
    ])['results']['items'] ?? [];

    $batch = [];
    foreach ($items as $row) {
        $erpRef = registrarEnErp($row); // amount = efectivo a rendir; amount_total = total cobranza
        $batch[] = [
            'code' => $row['code'],
            'external_reference' => $erpRef,
        ];
    }

    if ($batch === []) {
        return;
    }

    $result = vascoPost('/v2/sync/collections-deliver', [
        'trace_id' => $traceId,
        'delivered_by' => 'job.rendicion',
        'items' => $batch,
    ]);

    if (!empty($result['results']['failed'])) {
        logFailed($result['results']['failed']);
    }
}
```

---

## 5. Campos que debe persistir vascorp (recomendado)

| Campo Vasco | Uso en ERP |
|-------------|------------|
| `code` (`COB-*`) | Clave anti-duplicados al importar |
| `id` | ID numérico Vasco (alternativa en POST) |
| **`amount`** | **Efectivo a rendir en caja** |
| `amount_total` | Total de la cobranza (todos los medios); no confundir con `amount` |
| `seller.username` | Vendedor |
| `customer.external_id` | Cliente |
| `external_reference` (al POST) | Tu nº de rendición — ya queda en auditoría Vasco |

---

## 6. Errores frecuentes

| Síntoma | Causa |
|---------|--------|
| Lista vacía | No hay cobros en campo o ya están `delivered` |
| `Cobranza no encontrada` | `code` incorrecto o typo |
| `no está pendiente de entrega` | Ya entregada o anulada |
| `ya fue entregada a empresa; no se puede anular` | Intentó cancel sobre `delivered` |
| `reason es obligatorio` | Falta motivo en cancel |
| 400 auth | `API_KEY` incorrecta |

---

## 7. No hacer en vascorp

- No intentar cambiar monto o cliente vía este API (solo cambio de estado a entregado o anulado).
- No usar JWT de vendedor; solo **API Key** servidor a servidor.
- No asumir que Vasco imputa a facturas; eso es 100 % ERP.
- No anular cobranzas ya `delivered` por `collections-cancel` (fuera de alcance).
- **No usar `amount_total` como monto de caja**; la rendición de efectivo es siempre `amount`.

---

## 8. Contacto / referencia Vasco

- Tablas: `collections`, `collection_payments`, `collection_audits`
- Estados: `pending_delivery` → `delivered` | `cancelled` (sin cash → `delivered` al registrar)
- Postman: carpeta **V2 · Sync vascorp** → requests de cobranzas
