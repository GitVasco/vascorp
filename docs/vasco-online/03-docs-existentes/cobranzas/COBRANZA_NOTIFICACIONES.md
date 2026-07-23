# Cobranzas — Notificación al cliente (plan técnico)

**Contexto:** el cliente debe enterarse **en el acto** de que entregó efectivo al vendedor. WhatsApp automático es un canal; la **cuenta del cliente** (portal, fase 2) es el canal persistente.

**Principio:** la cobranza **nunca** se bloquea por teléfono inválido o ausente.

**Validación de celular (ya implementada):** `api/helpers/phone.php` — móvil Perú `9XXXXXXXX` → E.164 sin `+` → `519XXXXXXXX`.

---

## Modelo híbrido (capas)

| Capa | Qué hace | Depende de |
|------|----------|------------|
| **1. En campo** | Ticket virtual + compartir (imagen / Web Share) | Solo admin |
| **2. Captura opcional** | Vendedor registra celular si el maestro no sirve | Vasco + cola vascorp |
| **3. WhatsApp auto** | Evolution API al número válido (maestro o capturado) | Capa 2 + Evolution |
| **4. Cuenta cliente** | Cobranza visible al login del cliente | Portal fase 2 |
| **5. Maestro vascorp** | Corrección definitiva de `phone_customer` | Sync vascorp |

---

## Fase 1.1 — Captura de celular en cobro + ticket compartible

**Objetivo:** si no hay celular válido en maestro, el vendedor puede capturarlo; el cliente recibe constancia inmediata vía ticket (sin esperar vascorp).

### 1.1.1 Migración `0013_collections_notification_phone.sql`

```sql
-- collections: teléfono usado para notificar esta cobranza (capturado en campo o copiado del maestro)
ALTER TABLE `collections`
  ADD COLUMN `notification_phone_collection` varchar(16) DEFAULT NULL
    COMMENT 'Celular E.164 sin + (ej. 51987654321). Maestro o captura en visita.',
  ADD COLUMN `notification_phone_source_collection` varchar(20) DEFAULT NULL
    COMMENT 'customer_master | field_capture | none',
  ADD COLUMN `client_notified_in_person_collection` tinyint(1) NOT NULL DEFAULT 0
    COMMENT '1=vendedor confirmó que mostró/compartió ticket al cliente en campo.',
  ADD KEY `idx_collections_notification_phone` (`notification_phone_collection`);
```

**Reglas al insertar (`CollectionService::register`):**

| Situación | `notification_phone_collection` | `notification_phone_source_collection` |
|-----------|-------------------------------|----------------------------------------|
| Maestro válido | `519…` del cliente | `customer_master` |
| Maestro vacío/inválido + vendedor captura | `519…` validado | `field_capture` |
| Sin celular usable | `NULL` | `none` |

Auditoría `created`: incluir en `metadata_collection_audit` → `notification_phone`, `notification_phone_source`, `whatsapp_eligible` (bool).

### 1.1.2 Backend admin

| Archivo | Cambio |
|---------|--------|
| `admin/src/Repositories/CollectionRepository.php` | Insert/select nuevas columnas |
| `admin/src/Services/CollectionService.php` | Aceptar `notification_phone` opcional en POST; resolver fuente; `buildWhatsappStatus()` ampliado |
| `admin/ajax/collections/register.php` | Pasar `notification_phone` del POST |
| `api/helpers/phone.php` | Sin cambios (ya existe) |

**POST `/ajax/collections/register.php` (nuevos campos opcionales):**

| Campo | Tipo | Notas |
|-------|------|-------|
| `notification_phone` | string | Solo si maestro inválido/ausente; validar con `vasco_validate_mobile_pe` |
| `client_notified_in_person` | `0` \| `1` | Checkbox en confirmación |

**Respuesta JSON (ampliar `whatsapp`):**

```json
{
  "whatsapp": {
    "can_notify": true,
    "phone_e164": "51987654321",
    "phone_display": "+51 987 654 321",
    "phone_source": "field_capture",
    "validation_error": null,
    "queue_status": "pending_send"
  }
}
```

### 1.1.3 UI visita — wizard cobrar

| Pantalla | Cambio |
|----------|--------|
| `actions/collect.php` | Si `!phone_whatsapp_valid`: bloque opcional «Celular para notificaciones» (paso 2 o 3) |
| `visit-collect.js` | Enviar `notification_phone` si el usuario lo completó; validación cliente con patrón `mobile-pe` |
| `actions/collect-success.php` | Badge: «Ticket mostrado al cliente» si aplica; enlace **Compartir ticket** (placeholder hasta imagen) |
| `visit.css` | Estilos bloque captura celular + estado warn/ok |

**UX paso confirmar (3):**

- [ ] Checkbox: *Confirmo que el cliente me entregó este efectivo…* (ya existe)
- [ ] Checkbox opcional: *Mostré o compartí el ticket al cliente*
- [ ] Si sin celular maestro: input celular + texto de ayuda (formato 987 654 321)

### 1.1.4 Compartir ticket (sin Evolution)

| Tarea | Detalle |
|-------|---------|
| Botón «Compartir» en éxito | `navigator.share({ title, text, url })` o descarga cuando exista imagen |
| Fase corta (MVP) | Compartir texto: código ticket + monto + cliente (`Web Share API`) — **implementado** |
| Post-MVP | PNG del ticket: generación servidor, storage, descarga y adjunto WhatsApp — ver `docs/vasco-online/03-docs-existentes/cobranzas/COBRANZA_TICKET_IMAGEN.md` |

### 1.1.5 QA Fase 1.1

- [x] Cliente con celular válido en maestro → no pide captura; `source = customer_master`
- [x] Cliente sin celular → captura opcional; cobranza OK con `field_capture`
- [x] Celular inválido en captura → error 400, no registra
- [x] Sin celular y sin captura → cobranza OK; `source = none`; UI advierte
- [x] Auditoría `created` con metadata de teléfono
- [x] Compartir ticket (texto) en pantalla de éxito

**Issue sugerido:** `#18-feat: captura de celular y notificación en cobranza (fase 1.1)`

---

## Post-MVP — Ticket virtual como imagen (PNG)

**Fuera del MVP.** Plan técnico completo: **`docs/vasco-online/03-docs-existentes/cobranzas/COBRANZA_TICKET_IMAGEN.md`**.

Resumen:

| Tema | Detalle |
|------|---------|
| Objetivo | Descargar/compartir PNG; mismo archivo para WhatsApp Evolution |
| BD | `ticket_image_path_collection` (migración 0012, sin uso aún) |
| Auditoría | `ticket_image_saved` |
| Enfoque recomendado | Generación PHP + QR real + `storage/collections/YYYY/MM/` |
| UI | Ampliar éxito y detalle (`visit-collect-success.js`) |
| Alternativa rápida | html2canvas en cliente (sin storage; solo parche) |

**Issue sugerido:** `#18-feat: ticket virtual como imagen PNG (storage + compartir)`

---

## Fase 1.2 — Cola de contactos pendientes para vascorp

> **Nota:** esta fase puede **unificarse** con el módulo **Gestionar** (`docs/vasco-online/03-docs-existentes/portal-visita/GESTION_CLIENTE_VISITA.md`), que define una bandeja general `customer_field_updates` en lugar de solo teléfonos de cobranza.

**Objetivo:** cuando el vendedor captura un celular en campo, vascorp puede actualizar el maestro sin perder trazabilidad.

### 1.2.1 Migración `0014_customer_contact_pending.sql`

```sql
CREATE TABLE IF NOT EXISTS `customer_contact_pending` (
  `id_customer_contact_pending` int(11) NOT NULL AUTO_INCREMENT,
  `id_customer_customer_contact_pending` int(11) NOT NULL,
  `phone_customer_contact_pending` varchar(16) NOT NULL COMMENT 'E.164 sin +',
  `source_customer_contact_pending` varchar(20) NOT NULL DEFAULT 'field_capture',
  `id_collection_customer_contact_pending` int(11) DEFAULT NULL COMMENT 'Cobranza que originó la captura',
  `id_user_seller_customer_contact_pending` int(11) NOT NULL,
  `status_customer_contact_pending` varchar(20) NOT NULL DEFAULT 'pending'
    COMMENT 'pending | synced | rejected | superseded',
  `trace_id_customer_contact_pending` varchar(64) NOT NULL,
  `notes_customer_contact_pending` text DEFAULT NULL,
  `date_created_customer_contact_pending` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_resolved_customer_contact_pending` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_customer_contact_pending`),
  KEY `idx_ccp_customer` (`id_customer_customer_contact_pending`),
  KEY `idx_ccp_status` (`status_customer_contact_pending`),
  KEY `idx_ccp_created` (`date_created_customer_contact_pending`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Celulares capturados en campo pendientes de confirmar en vascorp.';
```

**Al registrar cobranza con `field_capture`:**

1. Insert en `customer_contact_pending` (`status = pending`).
2. Si ya existe `pending` para el mismo cliente con otro número → marcar anterior `superseded` o rechazar según regla de negocio (documentar en implementación).

### 1.2.2 API v2 — consumo vascopro

**Nuevo endpoint:** `GET /v2/sync/customer-contacts-pending`

| Query | Descripción |
|-------|-------------|
| `status` | `pending` (default) |
| `limit` | máx. 500 |

**Respuesta:**

```json
{
  "ok": true,
  "trace_id": "...",
  "items": [
    {
      "id": 1,
      "customer_external_id": "12345",
      "doc_type": "6",
      "doc_number": "20123456789",
      "phone_e164": "51987654321",
      "phone_display": "+51 987 654 321",
      "collection_code": "COB-2026-0142",
      "captured_at": "2026-06-16T10:30:00Z",
      "seller_username": "jperez"
    }
  ]
}
```

**Confirmación:** `POST /v2/sync/customer-contacts-pending/ack`

```json
{
  "items": [
    { "id": 1, "status": "synced" },
    { "id": 2, "status": "rejected", "reason": "Número incorrecto en ERP" }
  ]
}
```

Vascopro actualiza vascorp → próximo `customers-bulk` trae `phone` corregido → Vasco marca `synced`.

**Opcional:** al `ack` con `synced`, actualizar `customers.phone_customer` si vascorp aún no corrió bulk (solo si negocio lo aprueba).

### 1.2.3 Admin supervisión

| Ruta / pantalla | Descripción |
|-----------------|-------------|
| `/customers` o módulo cobranzas | Filtro «Sin celular válido» |
| Listado pendientes | Tabla `customer_contact_pending` status=pending (solo roles supervisión) |

### 1.2.5 QA Fase 1.2

- [ ] Captura en campo crea fila `pending`
- [ ] vascopro GET devuelve pendientes con API Key
- [ ] ack `synced` / `rejected` actualiza estado
- [ ] Tras `customers-bulk` con phone válido, pendiente puede cerrarse

**Issue sugerido:** `#18-feat: cola customer_contact_pending y API vascorp`

**Doc Postman:** `postman/VASCORP_SYNC_CONTACTS.md` + samples.

---

## Fase 1.3 — Envío WhatsApp (Evolution API)

**Objetivo:** notificación automática cuando `can_notify = true`.

### 1.3.1 Servicio

| Componente | Ubicación sugerida |
|------------|-------------------|
| `WhatsappNotificationService` | `admin/src/Services/` o `api/services/` |
| Config `.env` | `EVOLUTION_API_URL`, `EVOLUTION_API_KEY`, `EVOLUTION_INSTANCE` |
| Job / cola | Tabla `notification_queue` o procesar síncrono post-registro (MVP: cola simple en BD) |

### 1.3.2 Migración `0015_notification_queue.sql` (opcional)

```sql
CREATE TABLE IF NOT EXISTS `notification_queue` (
  `id_notification_queue` int(11) NOT NULL AUTO_INCREMENT,
  `channel_notification_queue` varchar(20) NOT NULL DEFAULT 'whatsapp',
  `id_collection_notification_queue` int(11) DEFAULT NULL,
  `phone_notification_queue` varchar(16) NOT NULL,
  `payload_notification_queue` text NOT NULL COMMENT 'JSON mensaje/plantilla',
  `status_notification_queue` varchar(20) NOT NULL DEFAULT 'pending',
  `attempts_notification_queue` int(11) NOT NULL DEFAULT 0,
  `last_error_notification_queue` text DEFAULT NULL,
  `trace_id_notification_queue` varchar(64) NOT NULL,
  `date_created_notification_queue` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_sent_notification_queue` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_notification_queue`),
  KEY `idx_nq_status` (`status_notification_queue`),
  KEY `idx_nq_collection` (`id_collection_notification_queue`)
);
```

**Flujo:**

1. Tras `register` OK → si `whatsapp.can_notify` → insert cola `pending`.
2. Worker/cron o hook inmediato → Evolution API → `whatsapp_sent_collection = 1` + audit `whatsapp_sent`.
3. Fallo → reintentos + `last_error`; cobranza ya registrada (no rollback).

### 1.3.3 Plantilla mensaje (borrador)

```
Hola {cliente}, registramos su pago en efectivo de S/ {monto}.
Ticket: {ticket_code}
Código: {code_collection}
— {empresa}
```

Adjunto imagen ticket cuando exista `ticket_image_path_collection`.

### 1.3.4 QA Fase 1.3

- [ ] Envío exitoso marca `whatsapp_sent_collection`
- [ ] Fallo de Evolution no anula cobranza
- [ ] Auditoría `whatsapp_sent` con trace_id
- [ ] Usa `notification_phone_collection` (maestro o field_capture)

**Issue sugerido:** `#18-feat: cola y envío WhatsApp Evolution API`

---

## Fase 2.0 — Notificación en cuenta del cliente (portal)

**Objetivo:** canal principal a medio plazo; el cliente ve cobranzas sin depender de WhatsApp.

| Tarea | Detalle | Estado |
|-------|---------|--------|
| Auth portal | RUC/DNI + clave (`PRODUCTO.md` fase 2) | Hecho |
| API lectura | Listado y detalle cobranzas por cliente autenticado | Hecho (admin v2) |
| UI | Listado cobranzas + detalle ticket | Hecho |
| Badge «nuevo» | `customer_portal_notification_reads` + API `portal/unread-collections` | **Hecho** — ver `PORTAL_CLIENTE.md` |
| Email respaldo | Si `email_customer` válido (opcional 1.x) | Pendiente |

**Dependencia:** portal no bloquea fases 1.1–1.3.

---

## Orden de entrega recomendado

| # | Entrega | Esfuerzo | Valor inmediato |
|---|---------|----------|-----------------|
| **1** | Fase 1.1 — captura celular + compartir texto | Medio | Alto |
| **2** | Ticket imagen + compartir PNG | Medio | Alto — ver `docs/vasco-online/03-docs-existentes/cobranzas/COBRANZA_TICKET_IMAGEN.md` |
| **3** | Fase 1.2 — cola vascorp | Medio | Medio (datos maestros) |
| **4** | Fase 1.3 — Evolution API | Medio-alto | Alto (automatización) |
| **5** | Fase 2.0 — portal cliente | Alto | Muy alto (cuenta) |

---

## Archivos existentes a tocar (resumen Fase 1.1)

```
migrations/0013_collections_notification_phone.sql   (nuevo)
admin/src/Repositories/CollectionRepository.php
admin/src/Services/CollectionService.php
admin/ajax/collections/register.php
admin/views/pages/visit/actions/collect.php
admin/views/pages/visit/actions/collect-success.php
admin/public/customs/js/visit/visit-collect.js
admin/public/customs/css/visit/visit.css
docs/vasco-online/03-docs-existentes/portal-visita/VISITA_VENDEDOR_CHECKLIST.md                  (actualizar paso 10)
postman/vasco-api.postman_collection.json          (fase 1.2)
```

---

## Reglas de negocio (checklist implementación)

- [ ] Cobranza nunca bloqueada por teléfono
- [ ] Celular capturado en campo ≠ maestro hasta ack vascorp (salvo política explícita de overwrite)
- [ ] Un solo número «activo» por cobranza en `notification_phone_collection`
- [ ] Toda captura y envío WhatsApp con `trace_id` + auditoría
- [ ] Vendedor siempre puede dar constancia en persona (ticket + compartir)

---

## Referencias

- Validación celular: `api/helpers/phone.php`, `ValidationHelper::validateMobilePe()`
- Sync clientes: `postman/VASCORP_SYNC.md`
- Producto: `docs/vasco-online/03-docs-existentes/producto/PRODUCTO.md` (notificaciones, ticket imagen, portal)
- Ticket imagen (post-MVP): `docs/vasco-online/03-docs-existentes/cobranzas/COBRANZA_TICKET_IMAGEN.md`
- Cobranzas BD: `migrations/0012_create_collections_tables.sql`
