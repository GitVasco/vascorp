# vascorp — implementar sync de gestión de cliente en visita (handoff)

Documento para llevar al proyecto **vascorp/vascopro** y consumir datos capturados por vendedores en campo (celular WhatsApp, consentimiento, notas).

**No requiere endpoint nuevo en Vasco.** Ya están implementados:

| Acción | Método | Ruta |
|--------|--------|------|
| Listar bandeja | `GET` | `/v2/sync/customer-field-updates` |
| Confirmar / rechazar | `POST` | `/v2/sync/customer-field-updates/ack` |

Contrato HTTP detallado: [`postman/VASCORP_SYNC_FIELD_UPDATES.md`](../postman/VASCORP_SYNC_FIELD_UPDATES.md)  
Contexto producto: [`GESTION_CLIENTE_VISITA.md`](GESTION_CLIENTE_VISITA.md)

---

## Checklist (definition of done)

- [ ] Maestro de clientes ya sincronizado (`POST /v2/sync/customers-bulk`)
- [ ] Job periódico consulta `GET /v2/sync/customer-field-updates?status=pending`
- [ ] Por cada ítem: localizar cliente en ERP y actualizar celular / flags según reglas abajo
- [ ] Tras aplicar en ERP: `POST /v2/sync/customer-field-updates/ack` con `synced` o `rejected`
- [ ] Si `rejected`: enviar `rejection_reason` legible (el vendedor lo ve en Vasco)
- [ ] Log de `failed[]` y reintento de ítems fallidos
- [ ] (Opcional) Próximo `customers-bulk` trae el maestro ya corregido a Vasco

---

## 1. Modelo de datos: bandeja vs maestro

| Capa | Tabla Vasco | Quién escribe | Quién consume |
|------|-------------|---------------|---------------|
| **Bandeja** | `customer_field_updates` | Vendedor en visita (admin) | **vascorp** (GET + ack) |
| **Maestro** | `customers` | vascorp (`customers-bulk`) | Vasco (cobranzas, visita, etc.) |

**Regla:** al guardar la gestión en visita, Vasco **no** modifica `customers.phone_customer`. vascorp debe leer la bandeja, actualizar el ERP y confirmar con ack.

La cuenta **portal** (si el vendedor la solicitó) se provisiona en Vasco al guardar; vascorp solo recibe el flag `portal_account_requested` como referencia — no debe crear usuario en Vasco.

---

## 2. Qué construir en vascorp

Un **job programado** (cada X minutos o manual):

```
CustomerFieldUpdateSyncJob
├── generar trace_id único por corrida
├── GET pending (limit 100–500)
├── por cada ítem:
│   ├── resolver cliente ERP (external_id o doc_type + doc_number)
│   ├── si whatsapp_consent: actualizar celular en ERP desde phone_e164
│   ├── si solo portal_account_requested: marcar flag interno / log (opcional)
│   └── acumular ack synced | rejected
└── POST ack con todos los ítems procesados
```

### Pseudocódigo

```php
$traceId = 'vascorp-gestion-' . date('Ymd-His');

$response = getFromVasco('/v2/sync/customer-field-updates', [
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

    if ($item['whatsapp_consent'] && $item['phone_e164'] !== null) {
        $phoneLocal = substr($item['phone_e164'], 2); // 51987654321 → 987654321
        updateErpMobile($erpClient, $phoneLocal);
        setWhatsappConsentFlag($erpClient, true);
    }

    // portal_account_requested: solo informativo; la cuenta ya está en Vasco
    if (!empty($item['notes'])) {
        logVisitNote($erpClient, $item['notes'], $item['managed_at']);
    }

    $ackItems[] = ['id' => $item['id'], 'result' => 'synced'];
}

if ($ackItems !== []) {
    postToVasco('/v2/sync/customer-field-updates/ack', [
        'trace_id' => $traceId,
        'ack_by'   => 'job.gestion-cliente',
        'items'    => $ackItems,
    ]);
}
```

### HTTP

| Item | Valor |
|------|-------|
| URL dev | `http://api.vasco.io:8084/v2/sync/customer-field-updates` |
| URL prod | `https://api.vasco.io/v2/sync/customer-field-updates` |
| Auth | `Authorization: {API_KEY}` (misma API Key v2 que otros syncs) |
| Timeout GET | **60 s** |
| Timeout POST ack | **60 s** |

---

## 3. Qué viene en cada ítem (GET)

| Campo | Tipo | Uso en vascorp |
|-------|------|----------------|
| `id` | int | ID en Vasco; obligatorio en el ack |
| `phone_e164` | string \| null | Celular normalizado Perú: `519XXXXXXXX` (11 dígitos). Null si la gestión fue solo portal. |
| `whatsapp_consent` | bool | `true` = cliente aceptó avisos por WhatsApp |
| `portal_account_requested` | bool | Informativo; Vasco ya provisionó portal si aplica |
| `managed_at` | datetime ISO | Cuándo el vendedor capturó con el cliente presente |
| `notes` | string \| null | Observaciones opcionales de la visita |
| `visit_trace_id` | string | Trazabilidad de la visita en Vasco |
| `customer.external_id` | string \| null | ID estable en vascorp (preferido para match) |
| `customer.doc_type` | string | SUNAT-06: `1` DNI, `6` RUC, etc. |
| `customer.doc_number` | string | Sin espacios |
| `customer.code` | string | Código interno Vasco (ej. `CL00045`) |
| `seller.username` | string | DNI/RUC del vendedor que gestionó |

### Match de cliente en ERP

Orden recomendado:

1. `customer.external_id` → ID en vascorp  
2. Si no hay: `doc_type` + `doc_number`  
3. Si falla → ack `rejected` con motivo claro

### Celular

- Formato Vasco: **E.164 Perú** `51987654321`
- En ERP suele guardarse **9 dígitos** `987654321` → quitar prefijo `51`
- Validar en ERP con la misma regla: empieza en `9`, 9 dígitos

### Solo portal (sin WhatsApp)

Puede llegar `whatsapp_consent: false`, `phone_e164: null`, `portal_account_requested: true`.  
En ese caso vascorp no debe exigir celular; ack `synced` si no hay nada que actualizar en ERP, o registrar el flag internamente.

---

## 4. Ack: confirmar o rechazar

```
POST /v2/sync/customer-field-updates/ack
```

| `result` | Cuándo usar |
|----------|-------------|
| `synced` | ERP actualizado correctamente (o no había cambio que aplicar) |
| `rejected` | Cliente inexistente, celular inválido en ERP, duplicado, etc. |

**Obligatorio si `rejected`:** `rejection_reason` (texto legible). El vendedor lo ve en la app Vasco.

Estados en Vasco tras el ack:

| Estado | Significado |
|--------|-------------|
| `pending` | Aún no procesado por vascorp |
| `synced` | vascorp confirmó |
| `rejected` | vascorp rechazó (con motivo) |
| `superseded` | Reemplazado por una gestión más nueva del mismo cliente |

---

## 5. Respuestas y errores

| HTTP | Acción en vascorp |
|------|-------------------|
| **200** | GET/ack OK |
| **207** | Ack parcial: revisar `failed[]`, corregir y reenviar solo esos ítems |
| **400** | Parámetros inválidos; revisar log |
| Red / timeout GET | Reintentar la misma consulta (idempotente) |
| Red / timeout POST ack | Reintentar el mismo ack; ítems ya `synced`/`rejected` aparecen en `already_processed` |

### Errores frecuentes en `failed[]` (ack)

| Mensaje | Solución |
|---------|----------|
| Registro de gestión no encontrado | ID incorrecto o borrado |
| result debe ser synced o rejected | Corregir payload |
| rejection_reason es obligatorio | Incluir motivo si `rejected` |
| El registro no está en estado pending | Ya fue procesado; ignorar o revisar `already_processed` |

---

## 6. Prueba mínima (curl)

### Paso A — Listar pendientes

```bash
curl -s "http://api.vasco.io:8084/v2/sync/customer-field-updates?status=pending&limit=10" \
  -H "Authorization: TU_API_KEY"
```

### Paso B — Confirmar un ítem

```bash
curl -s -X POST "http://api.vasco.io:8084/v2/sync/customer-field-updates/ack" \
  -H "Authorization: TU_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "trace_id": "vascorp-prueba-gestion-001",
    "ack_by": "dev.local",
    "items": [
      { "id": 12, "result": "synced" }
    ]
  }'
```

### Paso C — Verificar en Vasco

- Admin → visita al cliente → tile **Gestionar** debe mostrar badge **Gestionado** (si ack `synced`)
- O repetir GET con `status=synced` y filtrar por `id`

---

## 7. Orden de integración (fase 1 Vasco)

```
1. POST /v2/sync/customers-bulk              ← maestro (prerrequisito)
2. Vendedores gestionan clientes en visita    ← genera filas pending
3. GET  /v2/sync/customer-field-updates      ← este documento
4. POST /v2/sync/customer-field-updates/ack
5. (Opcional) POST /v2/sync/customers-bulk  ← maestro con celular ya corregido
```

---

## 8. Fuera de alcance (no implementar en este sync)

- Crear usuarios portal en Vasco (ya lo hace el admin al guardar gestión)
- Modificar `customers` directamente vía API (solo bandeja + ack)
- Panel de supervisión en Vasco (pendiente producto)
- Envío de WhatsApp (Evolution API — otro flujo)

---

## 9. Archivos en repo Vasco

| Recurso | Ruta |
|---------|------|
| Contrato HTTP | `postman/VASCORP_SYNC_FIELD_UPDATES.md` |
| Sync clientes (maestro) | `postman/VASCORP_SYNC.md` |
| Implementación API | `api/services/v2/sync/CustomerFieldUpdateSyncService.php` |
| Migración bandeja | `migrations/0020_customer_field_updates.sql` |
| Migración portal | `migrations/0021_customer_portal_accounts.sql` |
