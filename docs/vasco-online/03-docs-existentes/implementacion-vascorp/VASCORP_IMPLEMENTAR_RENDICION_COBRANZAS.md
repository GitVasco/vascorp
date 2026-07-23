# vascorp — implementar rendición de cobranzas (handoff)

Documento para llevar al proyecto **vascorp/vascopro** y cerrar la integración de **validación de efectivo cobrado en campo**.

Contrato HTTP detallado: [`postman/VASCORP_SYNC_COLLECTIONS_DELIVER.md`](../../../../postman/VASCORP_SYNC_COLLECTIONS_DELIVER.md)  
Guía extendida: [`VASCOPRO_SYNC_RENDICION_COBRANZAS.md`](../cobranzas/VASCOPRO_SYNC_RENDICION_COBRANZAS.md)  
Plantilla JSON: [`postman/samples/vascorp-collections-deliver.json`](../../../../postman/samples/vascorp-collections-deliver.json)

---

## Checklist (definition of done)

- [ ] Job o pantalla de caja consulta `GET /v2/sync/collections-pending-delivery`
- [ ] Filtros usados según negocio (`seller_username`, `since`, `limit` ≤ 500)
- [ ] Por cada cobranza confirmada en ERP: `POST /v2/sync/collections-deliver` con `code` o `id`
- [ ] Se envía `delivered_by` (usuario/proceso) y opcional `external_reference` (nº rendición ERP)
- [ ] Manejo de respuesta **207** (`failed[]`) y reintento selectivo
- [ ] Idempotencia: `already_delivered` no se trata como error
- [ ] Log de `trace_id` por corrida
- [ ] Verificación en Vasco admin → visita cliente → cobranza en **Entregado a empresa**

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

### Opción B — Pantalla de caja

```
Pantalla rendición
├── GET con seller_username o rango de fechas (since)
├── grilla: código COB, cliente, vendedor, monto, fecha
├── botón Confirmar → POST un ítem o batch
└── mostrar resultado (delivered / already_delivered / error)
```

---

## 3. HTTP

| Item | Valor |
|------|-------|
| URL dev GET | `http://api.vasco.io:8084/v2/sync/collections-pending-delivery` |
| URL dev POST | `http://api.vasco.io:8084/v2/sync/collections-deliver` |
| URL prod | `https://api.vasco.io/v2/sync/...` |
| Auth | `Authorization: {API_KEY}` |
| GET Content-Type | — |
| POST Content-Type | `application/json` |

### Ejemplo GET

```
GET /v2/sync/collections-pending-delivery?status=pending_delivery&limit=100&since=2026-06-01
Authorization: {API_KEY}
```

### Ejemplo POST

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
        $erpRef = registrarEnErp($row); // monto, cliente, vendedor
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
| `amount` | Monto rendido |
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
| 400 auth | `API_KEY` incorrecta |

---

## 7. No hacer en vascorp

- No intentar cambiar monto o cliente vía este API (solo cambio de estado a entregado).
- No usar JWT de vendedor; solo **API Key** servidor a servidor.
- No asumir que Vasco imputa a facturas; eso es 100 % ERP.

---

## 8. Contacto / referencia Vasco

- Tablas: `collections`, `collection_audits`
- Estados: `pending_delivery` → `delivered`
- Postman: carpeta **V2 · Sync vascorp** → requests de cobranzas
