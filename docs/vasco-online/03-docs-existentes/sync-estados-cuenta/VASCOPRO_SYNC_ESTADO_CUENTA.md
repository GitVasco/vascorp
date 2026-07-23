# vascopro → Vasco: sincronización de estados de cuenta

Guía completa para implementar el job de sync en **vascopro/vascorp**.

**Handoff (checklist para implementar en vascorp):** [`VASCORP_IMPLEMENTAR_ESTADOS_CUENTA.md`](../implementacion-vascorp/VASCORP_IMPLEMENTAR_ESTADOS_CUENTA.md)

Documento técnico del contrato HTTP: [`postman/VASCORP_SYNC_ACCOUNT.md`](../../../../postman/VASCORP_SYNC_ACCOUNT.md)  
Plantilla JSON batch: [`postman/samples/vascorp-account-statements-bulk.json`](../../../../postman/samples/vascorp-account-statements-bulk.json)  
Plantilla JSON finalize: [`postman/samples/vascorp-account-statements-finalize.json`](../../../../postman/samples/vascorp-account-statements-finalize.json)

---

## 1. Prerrequisito obligatorio

El maestro de **clientes** debe estar sincronizado antes.

```
POST /v2/sync/customers-bulk
```

Si envías una cuenta cuyo `doc_type` + `doc_number` no existe en Vasco, esa fila falla con:

> Cliente no encontrado en Vasco (sincronizar maestro de clientes primero).

**Llave del cliente en ambos syncs:** `doc_type` (SUNAT-06) + `doc_number` (sin espacios).  
No uses `code_customer` ni `external_id` como llave principal (vascorp puede tener códigos duplicados por documento).

---

## 2. Conexión

| Entorno | Base URL |
|---------|----------|
| Desarrollo | `http://api.vasco.io:8084` |
| Producción | `https://api.vasco.io` (ajustar según despliegue) |

### Headers (todos los requests)

```
Authorization: {API_KEY}
Content-Type: application/json
```

- `API_KEY` = valor de `API_KEY` en `.env` de Vasco (sin prefijo `Bearer`).
- Método: **POST** únicamente.
- Timeout recomendado del cliente HTTP: **120 s**.

### Health check (opcional)

```
GET /health
```

Sin API key.

---

## 3. Endpoint

```
POST /v2/sync/account-statements-bulk
```

### Límites

| Concepto | Valor |
|----------|-------|
| Máx. clientes por request | **500** |
| Máx. documentos pendientes por cliente | **500** |
| Estrategia | **Snapshot** (reemplaza todo el estado del cliente) |

---

## 4. Qué datos extraer en vascorp

### Pantalla origen

Cuentas por cobrar del cliente (ej. módulo donde ves TOTAL VENTA, DEUDA TOTAL, VENCIDO TOTAL y la grilla de documentos).

### Por cada cliente (1 objeto en `accounts[]`)

| Campo API | Origen vascorp (referencia) | Regla |
|-----------|----------------------------|-------|
| `doc_type` | Tipo documento identidad del cliente (SUNAT-06) | `1` DNI, `6` RUC, etc. |
| `doc_number` | Número documento del cliente | Sin espacios; único por persona |
| `deuda_total` | **DEUDA TOTAL S/** del resumen | Numérico ≥ 0 |
| `vencido_total` | **VENCIDO TOTAL S/** del resumen | Numérico ≥ 0, ≤ `deuda_total` |
| `pending_documents` | Filas con estado **PENDIENTE** y saldo > 0 | Ver tabla abajo |

### Por cada documento pendiente (`pending_documents[]`)

| Campo API | Columna vascorp (referencia) | Regla |
|-----------|------------------------------|-------|
| `doc_type` | `tipo_doc` | `01` factura, `09` proforma, etc. (2 dígitos) |
| `doc_number` | `num_cta` | Número del documento |
| `issue_date` | `fecha` (emisión) | `YYYY-MM-DD` o omitir |
| `due_date` | `fecha_ven` | `YYYY-MM-DD` o omitir |
| `amount` | `monto` | Monto original del documento |
| `balance` | `saldo` | Saldo pendiente; **debe ser > 0** |

### Qué NO enviar

| Dato | Motivo |
|------|--------|
| Filas **CANCELADO** / saldo 0 | No son deuda abierta |
| Movimientos de cancelación (`cod_pago` 80, 10, etc.) | Son abonos internos del ERP |
| `TOTAL VENTA` | Historial; no se usa en Vasco fase 1 |
| Protestado, banco, nro único, notas de pago | Operación interna vascorp |

---

## 5. Ejemplo de body completo

```json
{
  "trace_id": "vascorp-ec-20260615-001",
  "batch": 1,
  "accounts": [
    {
      "doc_type": "6",
      "doc_number": "20123456789",
      "deuda_total": 405881.63,
      "vencido_total": 137446.65,
      "pending_documents": [
        {
          "doc_type": "09",
          "doc_number": "0030019723",
          "issue_date": "2026-02-09",
          "due_date": "2026-05-10",
          "amount": 40145.39,
          "balance": 40145.39
        }
      ]
    }
  ]
}
```

### Campos raíz

| Campo | Obligatorio | Descripción |
|-------|-------------|-------------|
| `trace_id` | No | Trazabilidad (8–64 chars). **Obligatorio** si `finalize: true`. |
| `batch` | No | Número de lote para logs. |
| `finalize` | No | `true` en el último batch de la corrida (purga clientes ausentes). |
| `accounts` | Sí* | Arreglo de cuentas. *Puede ser `[]` con `finalize: true`. |

---

## 6. Respuestas HTTP

### 200 — Todo el batch OK

```json
{
  "status": 200,
  "results": {
    "ok": true,
    "trace_id": "vascorp-ec-20260615-001",
    "batch": 1,
    "processed": 120,
    "documents": 340,
    "purged": 0,
    "failed": [],
    "partial": false
  }
}
```

| Campo | Significado |
|-------|-------------|
| `processed` | Clientes guardados en el batch |
| `documents` | Total documentos pendientes insertados |
| `purged` | Clientes eliminados (solo cuando `finalize: true`) |

### 207 — Parcial (algunas filas fallaron)

Mismo cuerpo con `ok: false`, `partial: true` y `failed` con detalle. **Las filas válidas sí se guardaron.**

### 400 — Batch inválido

JSON mal formado, batch vacío, o ninguna cuenta válida.

### Errores por fila (`failed[]`)

```json
{
  "index": 2,
  "doc_type": "6",
  "doc_number": "20100070970",
  "message": "Cliente no encontrado en Vasco (sincronizar maestro de clientes primero)."
}
```

Otros mensajes frecuentes:

- `doc_type inválido (SUNAT-06).`
- `deuda_total es obligatorio y numérico.`
- `vencido_total no puede ser mayor que deuda_total.`
- `doc_type + doc_number duplicado en el mismo batch.`
- `Máximo 500 documentos pendientes por cliente.`

---

## 7. Algoritmo recomendado en vascopro

```
1. trace_id = "vascorp-ec-" + fecha + "-" + secuencia
2. Consultar clientes con deuda (o todos los activos con cuenta)
3. Para cada cliente:
   a. Normalizar doc_type + doc_number (SUNAT-06)
   b. Calcular deuda_total y vencido_total desde vascorp
   c. Listar solo filas PENDIENTE con saldo > 0 → pending_documents[]
4. Partir en batches de 500 clientes
5. Para cada batch (1..N-1):
   POST /v2/sync/account-statements-bulk
   { trace_id, batch: N, finalize: false, accounts: [...] }
6. Último batch (o request extra de cierre):
   POST con { trace_id, batch: N, finalize: true, accounts: [...] }
   → Vasco elimina clientes que ya no deben (no vinieron en esta corrida)
7. Si status 207: registrar failed[] y reintentar solo esas filas (corrigiendo causa)
8. Si status 400 y error de red: reintentar el batch completo (idempotente por snapshot)
```

### Idempotencia

Reenviar el mismo batch es seguro: Vasco **reemplaza** el snapshot del cliente (resumen + documentos pendientes).

### Frecuencia sugerida

- **Nocturna** para carga masiva inicial.
- **Incremental** (cada X horas) cuando el volumen esté validado.

---

## 8. Mapeo SUNAT-06 (cliente)

| doc_type | Documento |
|----------|-----------|
| `1` | DNI |
| `4` | Carnet extranjería |
| `6` | RUC |
| `7` | Pasaporte |
| `0`, `A`, `B` | Otros catálogo |

---

## 9. Tipos de documento comercial (pending_documents)

| tipo_doc | Descripción |
|----------|-------------|
| `01` | Factura |
| `03` | Boleta |
| `09` | Proforma |
| `07` | Nota de crédito |
| `08` | Nota de débito |

Códigos de **pago** (`80` efectivo, `10` descuento, etc.) no van en este sync.

---

## 10. Verificación después del sync

### Admin Vasco

Menú **Operación → Estados de cuenta** (`/account-statements`):

- Listado: cliente, deuda, vencido, cantidad de docs, última sync.
- Detalle (ojo): documentos pendientes con fechas y saldos.

### API v1 (debug)

```http
GET /v1/customer_account_summaries?linkTo=id_customer_customer_account_summary&equalTo={id_customer}
GET /v1/customer_account_pending_docs?linkTo=id_customer_customer_account_pending_doc&equalTo={id_customer}
```

---

## 11. cURL de prueba

```bash
curl -X POST "http://api.vasco.io:8084/v2/sync/account-statements-bulk" \
  -H "Authorization: TU_API_KEY" \
  -H "Content-Type: application/json" \
  -d @postman/samples/vascorp-account-statements-bulk.json
```

---

## 12. Tablas en Vasco (referencia)

| Tabla | Contenido |
|-------|-----------|
| `customer_account_summaries` | 1 fila por cliente: deuda, vencido, cantidad pendientes, fecha sync |
| `customer_account_pending_docs` | Documentos abiertos del snapshot |

---

## 13. Orden completo de integración vascopro

1. `POST /v2/sync/customers-bulk` — maestro clientes  
2. `POST /v2/sync/account-statements-bulk` — cuentas por cobrar  
3. Verificar en admin → **Estados de cuenta**  
4. Cobranzas en campo (admin visita) + `GET/POST /v2/sync/collections-*` — rendición — ver [`VASCOPRO_SYNC_RENDICION_COBRANZAS.md`](../cobranzas/VASCOPRO_SYNC_RENDICION_COBRANZAS.md)
