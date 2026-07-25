# vascopro → Vasco: rendición de cobranzas en efectivo

Guía completa para implementar el job de **consulta y validación de efectivo cobrado en campo** en **vascopro/vascorp**.

**Handoff (checklist para implementar en vascorp):** [`VASCORP_IMPLEMENTAR_RENDICION_COBRANZAS.md`](../implementacion-vascorp/VASCORP_IMPLEMENTAR_RENDICION_COBRANZAS.md)

Documento técnico del contrato HTTP: ver docs entregadas por VascoPro (`postman/VASCORP_SYNC_COLLECTIONS_DELIVER.md`).

---

## 0. Cambio importante — medios de pago (leer antes de tocar el job)

En Vasco una cobranza puede combinar **efectivo**, **billetera** (Yape/Plin) y **transferencia**. La rendición **sigue siendo solo del efectivo** en mano del vendedor.

| Campo en GET | Significado | Usar para rendir |
|--------------|-------------|------------------|
| **`amount`** | Monto en **efectivo** (`cash`) a entregar a caja | **Sí — este es el monto de rendición** |
| **`amount_total`** | Total de la cobranza (cash + wallet + transfer) | No; es informativo / imputación comercial |
| **`total_amount`** (resumen del GET) | Suma de los `amount` (cash) de los ítems | Totales de caja / pendientes |

**Reglas para no confundirse:**

1. **Usar siempre `amount` para la rendición en caja.** No sumar ni sustituir por `amount_total`.
2. Si la cobranza es **mixta** (p. ej. S/ 100 total = S/ 60 cash + S/ 40 Yape): `amount = 60`, `amount_total = 100`. En caja se rinden **60**.
3. Si el cliente pagó **solo billetera o solo transferencia**: la cobranza **no aparece** en este GET (no hay efectivo que rendir; estado interno `delivered` automático).
4. Cobranzas antiguas (solo efectivo): `amount` ≈ `amount_total` (comportamiento igual que antes).
5. La **imputación a documentos** en el ERP puede usar el total comercial (`amount_total`) según regla de negocio; eso es independiente de la **custodia de efectivo** (`amount`).

Pantalla en vascorp: `/rendicion-vasco-caja` muestra **Efectivo** (`amount`) y **Otros medios** (`amount_total − amount`).

---

## 1. Qué resuelve esta integración

Cuando un vendedor cobra **efectivo** en visita (solo o como parte de un pago mixto), Vasco deja esa parte en estado **`pending_delivery`** (efectivo en custodia del vendedor).

La empresa debe **validar la rendición** cuando el vendedor entrega **ese efectivo** en caja/administración. Ese evento se refleja en Vasco como **`delivered`**, visible para el vendedor y el cliente en el historial.

**vascorp** es quien orquesta ese cierre: consulta pendientes, registra la rendición en el ERP e informa a Vasco vía API.

---

## 2. Prerrequisitos

| Requisito | Motivo |
|-----------|--------|
| Maestro de clientes sincronizado | Los ítems traen `customer.external_id` y documento |
| Usuarios/vendedores en Vasco | `seller.id` y `seller.username` (RUC/DNI) identifican al cobrador |
| Cobranzas con efectivo registradas en admin | Sin cash pendiente, el GET devuelve `items: []` |
| `API_KEY` de Vasco en vascorp | Mismo mecanismo que sync de clientes y estados de cuenta |

Tablas en Vasco: `collections`, `collection_audits` (migración `0012`) y `collection_payments` (migración `0043`, líneas por medio).

---

## 3. Endpoints

| Método | Ruta | Uso |
|--------|------|-----|
| **GET** | `/v2/sync/collections-pending-delivery` | Listar cobranzas pendientes (o entregadas si `status=delivered`) |
| **POST** | `/v2/sync/collections-deliver` | Marcar una o más cobranzas como recibidas en empresa |
| **POST** | `/v2/sync/collections-cancel` | Anular cobranzas `pending_delivery` (cobro erróneo en caja) |

Autenticación: header `Authorization: {API_KEY}` (sin `Bearer`).

Contrato de anulación (respuesta VascoPro): [`../../02-respuestas-vascopro/2026-07-23-collections-cancel.md`](../../02-respuestas-vascopro/2026-07-23-collections-cancel.md)

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
        // 1. Validar en ERP (vendedor, cliente)
        // 2. Rendir item['amount'] (= efectivo). item['amount_total'] = total cobranza (informativo)
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
3. Al confirmar en UI, POST deliver con un solo ítem.
4. Si el cobro es erróneo, POST cancel con `reason` (obligatorio) → estado `cancelled` en Vasco.

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
| **`amount`** | **Efectivo PEN a rendir en caja** (no el total si hay mixto) |
| `amount_total` | Total de la cobranza (cash + wallet + transfer); informativo |
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

## 6b. POST cancel — reglas importantes

1. Solo desde **`pending_delivery`** → `cancelled`.
2. **`reason` obligatorio** por ítem (máx. 255).
3. **`cancelled_by`**: usuario/proceso ERP (auditoría).
4. **Idempotente**: ya anulada → `already_cancelled`.
5. **`delivered` no anulable** por este endpoint → `failed[]` con mensaje claro.
6. Tras anular, deja de aparecer en GET pendientes.

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
- [ ] GET tras cobro **solo efectivo** → aparece el `COB-*` con `amount` = total
- [ ] GET tras cobro **mixto** → aparece con `amount` = solo cash y `amount_total` = suma de medios
- [ ] Cobro **solo billetera/transfer** → **no** aparece en pendientes
- [ ] POST deliver → 200 y `status: delivered` en ítem
- [ ] Segundo POST mismo code → `already_delivered`
- [ ] Admin visita → historial muestra entregada
- [ ] `external_reference` visible en auditoría (consulta BD o futuro panel supervisión)
- [ ] POST cancel de `pending_delivery` → `cancelled` y sale del GET pendientes
- [ ] Segundo POST cancel → `already_cancelled`
- [ ] POST cancel de `delivered` → `failed[]` con mensaje claro
- [ ] `reason` + `cancelled_by` en `collection_audits`
- [ ] UI caja: columna **Efectivo** = `amount`; **Otros medios** = `amount_total − amount`

---

## 10. Orden en el roadmap vascopro

| Paso | Sync |
|------|------|
| 1 | `POST /v2/sync/customers-bulk` |
| 2 | `POST /v2/sync/account-statements-bulk` |
| 3 | **GET/POST rendición cobranzas** (este documento) |
| 4 | (Futuro) `GET/ack customer-contacts-pending` |

Ver orden completo: [`VASCOPRO_SYNC_ESTADO_CUENTA.md`](../sync-estados-cuenta/VASCOPRO_SYNC_ESTADO_CUENTA.md) §13.
