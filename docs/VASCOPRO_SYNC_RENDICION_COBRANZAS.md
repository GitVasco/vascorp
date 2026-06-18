# vascopro → Vasco: rendición de cobranzas en efectivo

Guía completa para implementar el job de **consulta y validación de efectivo cobrado en campo** en **vascopro/vascorp**.

**Handoff (checklist para implementar en vascorp):** [`VASCORP_IMPLEMENTAR_RENDICION_COBRANZAS.md`](VASCORP_IMPLEMENTAR_RENDICION_COBRANZAS.md)

Documento técnico del contrato HTTP: [`postman/VASCORP_SYNC_COLLECTIONS_DELIVER.md`](../postman/VASCORP_SYNC_COLLECTIONS_DELIVER.md)  
Plantilla JSON deliver: [`postman/samples/vascorp-collections-deliver.json`](../postman/samples/vascorp-collections-deliver.json)

---

## 1. Qué resuelve esta integración

Cuando un vendedor cobra en efectivo en visita, Vasco registra la cobranza con estado **`pending_delivery`** (efectivo en custodia del vendedor).

La empresa debe **validar la rendición** cuando el vendedor entrega el dinero en caja/administración. Ese evento se refleja en Vasco como **`delivered`**, visible para el vendedor y el cliente en el historial.

**vascorp** es quien orquesta ese cierre: consulta pendientes, registra la rendición en el ERP e informa a Vasco vía API.

---

## 2. Prerrequisitos

| Requisito | Motivo |
|-----------|--------|
| Maestro de clientes sincronizado | Los ítems traen `customer.external_id` y documento |
| Usuarios/vendedores en Vasco | `seller.id` y `seller.username` (RUC/DNI) identifican al cobrador |
| Cobranzas registradas en admin | Sin registros en campo, el GET devuelve `items: []` |
| `API_KEY` de Vasco en vascorp | Mismo mecanismo que sync de clientes y estados de cuenta |

No hay migración adicional en Vasco: las tablas `collections` y `collection_audits` ya existen (migración `0012`).

---

## 3. Endpoints

| Método | Ruta | Uso |
|--------|------|-----|
| **GET** | `/v2/sync/collections-pending-delivery` | Listar cobranzas pendientes (o entregadas si `status=delivered`) |
| **POST** | `/v2/sync/collections-deliver` | Marcar una o más cobranzas como recibidas en empresa |

Autenticación: header `Authorization: {API_KEY}` (sin `Bearer`).

---

## 4. Algoritmo recomendado en vascopro

### Job periódico (ej. cada 15 min o al cerrar caja)

```php
$traceId = 'vascorp-deliver-' . date('Ymd-His');

do {
    $response = getFromVasco('/v2/sync/collections-pending-delivery', [
        'status' => 'pending_delivery',
        'limit'  => 500,
        'trace_id' => $traceId,
    ]);

    $items = $response['results']['items'] ?? [];
    if ($items === []) {
        break;
    }

    $toDeliver = [];

    foreach ($items as $item) {
        // 1. Validar en ERP si aplica (vendedor, monto, cliente)
        // 2. Registrar rendición / imputación en vascorp
        $rendicionId = registrarRendicionEnErp($item);

        $toDeliver[] = [
            'code' => $item['code'],
            'external_reference' => $rendicionId,
        ];
    }

    if ($toDeliver !== []) {
        postToVasco('/v2/sync/collections-deliver', [
            'trace_id' => $traceId,
            'delivered_by' => 'job.rendicion',
            'items' => $toDeliver,
        ]);
    }

    // Si count < limit, no hay más en esta corrida
} while (count($items) >= 500);
```

### Variante: rendición manual desde pantalla de caja

1. Operador busca por vendedor o fecha en vascorp.
2. vascorp llama GET con `seller_username` o `since`.
3. Al confirmar en UI, POST con un solo ítem.

---

## 5. Mapeo de datos vascorp ↔ Vasco

### Cliente

| Vasco (GET) | vascorp |
|-------------|---------|
| `customer.external_id` | ID cliente en ERP (preferido) |
| `customer.doc_type` + `customer.doc_number` | Llave SUNAT-06 si no hay external_id |
| `customer.code` | Código Vasco (`VC…`) — referencia |

### Vendedor

| Vasco (GET) | vascorp |
|-------------|---------|
| `seller.id` | `users.id_user` en Vasco (si lo guardaron en sync) |
| `seller.username` | RUC/DNI del vendedor |
| `seller.name` | Nombre para mostrar |

### Cobranza

| Vasco | vascorp |
|-------|---------|
| `code` | Referencia única `COB-YYYY-####` — usar en POST deliver |
| `amount` | Monto PEN a rendir |
| `ticket_code` | Ticket virtual cliente (`TKT-*`) |
| `physical_ticket_code` | Recibo físico opcional |
| `notes` | Notas del vendedor |
| `created_at` | Fecha/hora del cobro en campo |

---

## 6. POST deliver — reglas importantes

1. **Identificar por `id` o `code`** — al menos uno por ítem.
2. **`delivered_by`**: usuario o proceso ERP (auditoría).
3. **`external_reference`**: nº de rendición, asiento contable, etc. (opcional, queda en auditoría).
4. **Idempotente**: reenviar un `code` ya entregado devuelve `already_delivered`, no error.
5. **Estados no válidos**: cobranzas `cancelled` fallan en `failed[]`.
6. **Límite**: 500 ítems por POST; paginar si hay más.

---

## 7. Respuestas y manejo de errores

| HTTP | Acción en vascorp |
|------|-------------------|
| 200 | Todo OK; continuar |
| 207 | Revisar `failed[]`; reintentar solo fallidos |
| 400 | Parámetros o body inválido; corregir y no reintentar en bucle |
| 400 (auth) | Revisar `API_KEY` |

Loguear siempre `trace_id` de la respuesta para cruzar con logs de Vasco.

---

## 8. Después del deliver en Vasco

- Estado en BD: `delivered` + `date_delivered_collection`.
- Auditoría obligatoria en `collection_audits`.
- El vendedor ve **Entregado a empresa** en `/visit/collections` del cliente.
- La **imputación a documentos** (cuenta más antigua) sigue siendo **solo en vascorp**; Vasco no toca estados de cuenta en este paso.

---

## 9. QA integración

- [ ] GET sin pendientes → `count: 0`
- [ ] GET tras cobro en campo → aparece el `COB-*` con monto y cliente correctos
- [ ] POST deliver → 200 y `status: delivered` en ítem
- [ ] Segundo POST mismo code → `already_delivered`
- [ ] Admin visita → historial muestra entregada
- [ ] `external_reference` visible en auditoría (consulta BD o futuro panel supervisión)

---

## 10. Orden en el roadmap vascopro

| Paso | Sync |
|------|------|
| 1 | `POST /v2/sync/customers-bulk` |
| 2 | `POST /v2/sync/account-statements-bulk` |
| 3 | **GET/POST rendición cobranzas** (este documento) |
| 4 | (Futuro) `GET/ack customer-contacts-pending` |

Ver orden completo: [`VASCOPRO_SYNC_ESTADO_CUENTA.md`](VASCOPRO_SYNC_ESTADO_CUENTA.md) §13.
