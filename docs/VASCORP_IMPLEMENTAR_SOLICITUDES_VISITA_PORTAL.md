# vascorp — implementar sync de solicitudes de visita del portal (handoff)

Documento para llevar al proyecto **vascorp/vascopro** y consumir solicitudes de apoyo que los clientes envían desde el portal Vasco.

**Endpoints Vasco (implementados):**

| Acción | Método | Ruta |
|--------|--------|------|
| Listar bandeja | `GET` | `/v2/sync/portal-visit-requests` |
| Confirmar / cerrar | `POST` | `/v2/sync/portal-visit-requests/ack` |

Contrato HTTP detallado: [`postman/VASCORP_SYNC_PORTAL_VISIT_REQUESTS.md`](../postman/VASCORP_SYNC_PORTAL_VISIT_REQUESTS.md)  
Contexto producto: [`PORTAL_CLIENTE.md`](PORTAL_CLIENTE.md)

---

## Checklist (definition of done)

- [ ] Maestro de clientes ya sincronizado (`POST /v2/sync/customers-bulk`)
- [ ] Job periódico consulta `GET /v2/sync/portal-visit-requests?status=pending`
- [ ] **Bandeja de cierre:** consultar también `?status=acknowledged` (o pantalla en ERP con solicitudes abiertas) para marcar `completed`
- [ ] Por cada ítem: resolver cliente en ERP (`external_id` o documento)
- [ ] Notificar al vendedor (`seller` en la respuesta; si es `null`, usar reglas internas de ruta)
- [ ] Ack `acknowledged` al registrar la solicitud en ERP / avisar al vendedor
- [ ] Ack `completed` cuando la visita o contacto se realizó (desde `pending` o `acknowledged`)
- [ ] Ack `rejected` + `rejection_reason` si no procede
- [ ] Log de `failed[]` y reintento de ítems fallidos

---

## 1. Modelo de datos

| Capa | Tabla Vasco | Quién escribe | Quién consume |
|------|-------------|---------------|---------------|
| **Solicitudes portal** | `customer_portal_visit_requests` | Cliente en portal (admin) | **vascorp** (GET + ack) |
| **Maestro** | `customers` | vascorp (`customers-bulk`) | Vasco (portal, visita, cobranzas) |

**Regla:** el cliente crea la solicitud en Vasco; vascorp **no** inserta filas. Solo lee, actúa en ERP y confirma con ack.

---

## 2.1 Importante: marcar como completada en vascorp

Tras el ack `acknowledged`, la solicitud **deja de salir** en `GET ?status=pending`.  
Para cerrarla en Vasco y liberar al cliente en el portal, vascorp debe:

1. **Listar** solicitudes en curso: `GET /v2/sync/portal-visit-requests?status=acknowledged`
2. Cuando el vendedor atendió en ERP → **POST** ack con `result: "completed"`

| Consulta GET | Cuándo usarla |
|--------------|----------------|
| `?status=pending` | Nuevas solicitudes del portal → ack `acknowledged` |
| `?status=acknowledged` | Solicitudes confirmadas, pendientes de visita → ack `completed` |

**Recomendación UI vascorp:** bandeja «Solicitudes portal» con columnas *Pendiente* y *En curso*; botón **Marcar atendida** que envía `completed`.

La API Vasco **no cambió**: `completed` sigue disponible desde `pending` o `acknowledged`.

---

## 2. Qué construir en vascorp

Job programado (cada X minutos o manual):

```
PortalVisitRequestSyncJob
├── generar trace_id único por corrida
├── GET pending (limit 100–500)
├── por cada ítem:
│   ├── resolver cliente ERP (external_id o doc_type + doc_number)
│   ├── asignar / notificar vendedor (seller.id o reglas de cartera)
│   └── acumular ack acknowledged | rejected
├── (job posterior o misma corrida si ya hubo visita)
│   └── ack completed para ítems acknowledged cerrados
└── POST ack con todos los ítems procesados
```

### Pseudocódigo

```php
$traceId = 'vascorp-portal-visits-' . date('Ymd-His');

$response = getFromVasco('/v2/sync/portal-visit-requests', [
    'status' => 'pending',
    'limit'  => 500,
    'trace_id' => $traceId,
]);

$ackItems = [];

foreach ($response['results']['items'] as $item) {
    $customer = $item['customer'];
    $erpClient = findErpClient(
        externalId: $customer['external_id'],
        docType: $customer['doc_type'],
        docNumber: $customer['doc_number'],
    );

    if ($erpClient === null) {
        $ackItems[] = [
            'id' => $item['id'],
            'result' => 'rejected',
            'rejection_reason' => 'Cliente no encontrado en ERP',
        ];
        continue;
    }

    $seller = $item['seller'];
    createVisitRequestTaskInErp(
        client: $erpClient,
        sellerId: $seller['id'] ?? null,
        message: $item['message'],
        vascoRequestId: $item['id'],
    );

    $ackItems[] = ['id' => $item['id'], 'result' => 'acknowledged'];
}

if ($ackItems !== []) {
    postToVasco('/v2/sync/portal-visit-requests/ack', [
        'trace_id' => $traceId,
        'ack_by'   => 'job.portal-visit-requests',
        'items'    => $ackItems,
    ]);
}
```

### Cierre de solicitud (visita realizada)

Cuando el vendedor confirme en ERP que atendió al cliente, listar primero las en curso:

```php
$open = getFromVasco('/v2/sync/portal-visit-requests', [
    'status' => 'acknowledged',
    'limit'  => 500,
    'trace_id' => $traceId,
]);

// Por cada ítem cerrado en ERP:
postToVasco('/v2/sync/portal-visit-requests/ack', [
    'trace_id' => $traceId,
    'ack_by'   => 'job.portal-visit-requests',
    'items'    => [
        ['id' => 3, 'result' => 'completed'],
    ],
]);
```

`completed` acepta solicitudes en estado `pending` o `acknowledged`.

---

## 3. HTTP

| Item | Valor |
|------|-------|
| URL dev | `http://api.vasco.io:8084/v2/sync/portal-visit-requests` |
| URL prod | `https://api.vasco.io/v2/sync/portal-visit-requests` |
| Auth | `Authorization: {API_KEY}` |
| Timeout GET | **60 s** |
| Timeout POST ack | **60 s** |

---

## 4. Estados

| Estado Vasco | Origen | Siguiente paso vascorp |
|--------------|--------|------------------------|
| `pending` | Cliente envió solicitud | ack `acknowledged` o `rejected` |
| `acknowledged` | vascorp tomó la solicitud | ack `completed` al cerrar |
| `completed` | Visita/contacto cerrado | — |
| `cancelled` | ack `rejected` | — |

---

## 5. Errores y reintentos

- Si `failed[]` en ack 207: corregir y reenviar solo esos ítems.
- Si el cliente ya tiene solicitud `pending` en portal, no puede crear otra hasta cerrar la actual (regla Vasco admin).

---

## Referencias

- Índice documentación: [`docs/README.md`](README.md)
- Sync clientes: [`postman/VASCORP_SYNC.md`](../postman/VASCORP_SYNC.md)
- Gestión en visita (vendedor → vascorp): [`VASCORP_IMPLEMENTAR_GESTION_CLIENTE.md`](VASCORP_IMPLEMENTAR_GESTION_CLIENTE.md)
