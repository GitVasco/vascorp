# vascorp — implementar sync de estados de cuenta (handoff)

Documento para llevar al proyecto **vascorp/vascopro** y cerrar la integración.

Contrato HTTP detallado: [`postman/VASCORP_SYNC_ACCOUNT.md`](../../../../postman/VASCORP_SYNC_ACCOUNT.md)  
Guía extendida: [`VASCOPRO_SYNC_ESTADO_CUENTA.md`](../sync-estados-cuenta/VASCOPRO_SYNC_ESTADO_CUENTA.md)  
Plantilla JSON: [`postman/samples/vascorp-account-statements-bulk.json`](../../../../postman/samples/vascorp-account-statements-bulk.json)

---

## Checklist (definition of done)

- [ ] Maestro de clientes ya sincronizado (`POST /v2/sync/customers-bulk`)
- [ ] Job exporta **solo clientes con deuda** (saldo > 0)
- [ ] Por cliente: `doc_type` + `doc_number` (SUNAT-06), `deuda_total`, `vencido_total`, `pending_documents[]`
- [ ] Solo docs **PENDIENTE** con `balance > 0` en `pending_documents`
- [ ] Batches de **máx. 500 clientes** por request
- [ ] **Mismo `trace_id`** en todos los batches de una corrida
- [ ] Último paso: request con **`finalize: true`** (mismo `trace_id`)
- [ ] Log de `failed[]` y reintento de filas fallidas
- [ ] Verificación en Vasco admin → **Estados de cuenta** cuadra con vascorp (muestra de clientes)

---

## 1. Prerrequisito

Sin esto, el sync de cuentas falla fila a fila:

```
POST /v2/sync/customers-bulk
```

La llave del cliente en **ambos** syncs es **`doc_type` + `doc_number`** (no `code_customer`, no `external_id`).

---

## 2. Qué construir en vascorp

Un **job programado** (o comando manual) con esta forma:

```
AccountStatementSyncJob
├── generar trace_id único por corrida
├── consultar clientes con deuda en el ERP
├── agrupar documentos pendientes por cliente
├── partir en batches de 500
├── POST batch 1..N-1  (finalize: false)
└── POST cierre        (finalize: true, accounts: [] o último batch)
```

### Pseudocódigo

```php
$traceId = 'vascorp-ec-' . date('Ymd-His'); // 8–64 chars [a-zA-Z0-9._:-]

$accounts = buildAccountsFromErp(); // ver sección 3
$batches = array_chunk($accounts, 500);

foreach ($batches as $i => $batch) {
    $isLast = ($i === count($batches) - 1);
    postToVasco([
        'trace_id' => $traceId,
        'batch'    => $i + 1,
        'finalize' => $isLast,
        'accounts' => $batch,
    ]);
}

// Alternativa: batches todos con finalize:false y al final:
// postToVasco(['trace_id' => $traceId, 'batch' => 99, 'finalize' => true, 'accounts' => []]);
```

### HTTP

| Item | Valor |
|------|-------|
| URL dev | `http://api.vasco.io:8084/v2/sync/account-statements-bulk` |
| URL prod | `https://api.vasco.io/v2/sync/account-statements-bulk` |
| Method | `POST` |
| Header | `Authorization: {API_KEY}` |
| Header | `Content-Type: application/json` |
| Timeout | **120 s** |

---

## 3. Qué extraer del ERP (vascorp)

### Origen

Pantalla / query de **cuentas por cobrar** del cliente (DEUDA TOTAL, VENCIDO TOTAL, grilla de documentos).

### Por cliente → 1 objeto en `accounts[]`

| Campo API | De dónde | Regla |
|-----------|----------|-------|
| `doc_type` | Tipo doc identidad cliente | SUNAT-06: `1` DNI, `6` RUC, etc. |
| `doc_number` | Número doc cliente | Sin espacios |
| `deuda_total` | DEUDA TOTAL S/ | ≥ 0, lo calcula vascorp |
| `vencido_total` | VENCIDO TOTAL S/ | ≥ 0, ≤ `deuda_total` |
| `pending_documents` | Filas abiertas | Solo PENDIENTE, saldo > 0 |

### Por documento → `pending_documents[]`

| Campo API | Columna ref. vascorp | Regla |
|-----------|---------------------|-------|
| `doc_type` | `tipo_doc` | `01` factura, `09` proforma, etc. |
| `doc_number` | `num_cta` | Número del documento |
| `issue_date` | `fecha` | `YYYY-MM-DD` (opcional) |
| `due_date` | `fecha_ven` | `YYYY-MM-DD` (opcional) |
| `amount` | `monto` | Monto original |
| `balance` | `saldo` | **> 0** obligatorio |

### NO enviar

| Excluir | Motivo |
|---------|--------|
| Filas CANCELADO / saldo 0 | No es deuda |
| `cod_pago` 80, 10, etc. | Movimientos de pago internos |
| TOTAL VENTA | No se usa en Vasco fase 1 |
| Clientes sin deuda | Vasco los limpia con `finalize` |

---

## 4. Sync completo con `finalize` (obligatorio)

vascorp manda **solo deudores**. Los que pagaron todo **dejan de aparecer** en el export.

Sin `finalize`, esos clientes quedarían con deuda vieja en Vasco.

### Regla

1. Generar **un** `trace_id` por corrida.
2. Enviar todos los batches con ese mismo `trace_id`.
3. Al terminar **todos** los batches OK → enviar cierre con `finalize: true`.

### Ejemplo batches 1 y 2 + cierre

```json
// Batch 1
{ "trace_id": "vascorp-ec-20260615-001", "batch": 1, "finalize": false, "accounts": [ /* hasta 500 */ ] }

// Batch 2
{ "trace_id": "vascorp-ec-20260615-001", "batch": 2, "finalize": false, "accounts": [ /* resto */ ] }

// Cierre (mismo trace_id)
{ "trace_id": "vascorp-ec-20260615-001", "batch": 3, "finalize": true, "accounts": [] }
```

Respuesta del cierre incluye `purged`: cantidad de clientes eliminados en Vasco (ya no deben).

---

## 5. Respuestas y errores

| HTTP | Acción en vascorp |
|------|-------------------|
| **200** | Batch OK. Si `finalize: true`, revisar `purged`. |
| **207** | Parcial: guardar `failed[]`, corregir y reenviar **solo** esas filas. |
| **400** | Batch inválido o JSON mal formado. Revisar log; reintentar batch. |
| Red / timeout | Reintentar el **mismo batch** (idempotente: snapshot por cliente). |

### Errores frecuentes en `failed[]`

| Mensaje | Solución |
|---------|----------|
| Cliente no encontrado en Vasco | Sync maestro clientes primero; verificar `doc_type` + `doc_number` |
| doc_type inválido | Usar catálogo SUNAT-06 |
| vencido_total > deuda_total | Corregir cálculo en ERP |
| duplicado en el mismo batch | Deduplicar por doc cliente antes de enviar |
| balance <= 0 | No incluir ese documento |

---

## 6. Prueba mínima (antes de corrida masiva)

### Paso A — 1 cliente real

```bash
curl -X POST "http://api.vasco.io:8084/v2/sync/account-statements-bulk" \
  -H "Authorization: TU_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "trace_id": "vascorp-prueba-001",
    "batch": 1,
    "finalize": true,
    "accounts": [{
      "doc_type": "6",
      "doc_number": "20100070970",
      "deuda_total": 405881.63,
      "vencido_total": 137446.65,
      "pending_documents": [{
        "doc_type": "09",
        "doc_number": "0030019723",
        "issue_date": "2026-02-09",
        "due_date": "2026-05-10",
        "amount": 40145.39,
        "balance": 40145.39
      }]
    }]
  }'
```

### Paso B — Verificar en Vasco

- Admin → **Operación → Estados de cuenta**
- O API: `GET /v1/customer_account_summaries?orderBy=debt_total_customer_account_summary&orderMode=DESC`

### Paso C — Corrida completa

Todos los deudores en batches + `finalize: true` al final.

---

## 7. Orden de integración (fase 1 Vasco)

```
1. POST /v2/sync/customers-bulk          ← maestro (ya hecho si clientes están en Vasco)
2. POST /v2/sync/account-statements-bulk ← este documento
3. Verificar admin /account-statements
4. (Después) cobranzas en Vasco — aún no implementado
```

---

## 8. Fuera de alcance (no implementar ahora)

- Cobranzas / pagos en Vasco
- Cancelaciones parciales embebidas en el sync
- Recalcular deuda en Vasco (Vasco solo refleja lo que manda vascorp)
- Elegir factura al cobrar (fase posterior)

---

## 9. Contacto / archivos en repo Vasco

| Recurso | Ruta |
|---------|------|
| Contrato HTTP | `postman/VASCORP_SYNC_ACCOUNT.md` |
| Sync clientes | `postman/VASCORP_SYNC.md` |
| Colección Postman | `postman/vasco-api.postman_collection.json` → carpeta **Estados de cuenta** |
| Sample batch | `postman/samples/vascorp-account-statements-bulk.json` |
| Sample finalize | `postman/samples/vascorp-account-statements-finalize.json` |
