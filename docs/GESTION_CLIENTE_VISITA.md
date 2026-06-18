# Gestionar cliente en visita — bandeja de datos para vascorp

**Estado:** implementado (admin + API v2). Handoff vascorp: [`docs/VASCORP_IMPLEMENTAR_GESTION_CLIENTE.md`](VASCORP_IMPLEMENTAR_GESTION_CLIENTE.md).

**Contexto:** en la etapa de control / adopción, el equipo va **cliente por cliente** mostrando la app y cómo se beneficia. Ahí conviene capturar datos que vascorp no trae bien (celular, autorizaciones, alta de cuenta portal) **sin depender del flujo de cobranza**.

---

## Qué es «Gestionar»

Nueva acción en el **dashboard del cliente en visita** (`/visit/customer`), junto a Estado de cuenta, Cobrar y Cobranzas.

| Tile | Rol |
|------|-----|
| **Gestionar** | Wizard corto para registrar información de adopción y contacto capturada **con el cliente presente** |

No reemplaza al maestro ERP: es una **bandeja en Vasco** que vascorp puede consumir cuando quiera.

---

## Principio de datos: maestro vs bandeja

| Capa | Dueño | Se pierde si no hay sync |
|------|-------|---------------------------|
| `customers.*` (maestro) | **vascorp** → sync `customers-bulk` | Se actualiza con lo que mande vascorp |
| **Bandeja de gestión** (tabla nueva en Vasco) | **Vasco** (captura en visita) | **No** — persiste aunque vascorp no consuma aún |

**Regla:** lo capturado en «Gestionar» **no pisa** `phone_customer` hasta que vascorp confirme. Un `customers-bulk` posterior puede cambiar el maestro; el registro de gestión queda como historial/auditoría.

Relación con cobranzas (ya implementado en fase 1.1):

- **Gestionar** = vía principal para limpiar celular y consentimientos.
- **Cobrar** = plan B: captura puntual en esa cobranza (`field_capture` en `collections`) si aún no hay maestro válido.

---

## Datos a capturar (evolutivo)

Fase mínima del wizard:

1. **Celular WhatsApp** — validación Perú (`api/helpers/phone.php` → `519XXXXXXXX`).
2. **Autorización** — checkbox: cliente acepta notificaciones de cobranzas/cuenta por WhatsApp (y luego email si aplica).
3. **Cliente gestionado** — fecha + usuario que realizó la visita de adopción.

Fase posterior (cuando exista portal cliente):

4. **Solicitud de cuenta portal** — crear / vincular acceso (RUC/DNI + contacto).
5. **Notas** — observaciones de la visita (opcional).

Futuro: más campos que vascorp no sincronice (contacto alterno, horario, preferencia de notificación, etc.) sin tocar el maestro hasta confirmación.

---

## Tabla propuesta: `customer_field_updates`

Nombre orientativo; migración futura `0014` o `0015` (ver también `customer_contact_pending` en `docs/COBRANZA_NOTIFICACIONES.md` — puede unificarse en este modelo).

```sql
-- Borrador conceptual (no aplicar aún)
CREATE TABLE customer_field_updates (
  id_customer_field_update       INT PK AUTO_INCREMENT,
  id_customer_customer_field_update INT NOT NULL,
  id_user_seller_customer_field_update INT NOT NULL,
  visit_trace_id_customer_field_update VARCHAR(64) NOT NULL,

  phone_e164_customer_field_update VARCHAR(16) NULL,
  whatsapp_consent_customer_field_update TINYINT(1) DEFAULT 0,
  portal_account_requested_customer_field_update TINYINT(1) DEFAULT 0,
  managed_at_customer_field_update TIMESTAMP NOT NULL,
  notes_customer_field_update TEXT NULL,

  status_customer_field_update VARCHAR(20) DEFAULT 'pending',
  -- pending | synced | rejected | superseded

  vascorp_ack_at_customer_field_update TIMESTAMP NULL,
  vascorp_ack_by_customer_field_update VARCHAR(64) NULL,
  rejection_reason_customer_field_update TEXT NULL,

  create_customer_field_update TEXT NULL,  -- JSON auditoría
  date_created_customer_field_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Estados:**

| `status` | Significado |
|----------|-------------|
| `pending` | Capturado en Vasco; vascorp no ha procesado |
| `synced` | vascorp actualizó ERP y confirmó (ack) |
| `rejected` | vascorp rechazó (dato incorrecto, duplicado, etc.) |
| `superseded` | Reemplazado por una gestión más reciente del mismo cliente |

Índices sugeridos: `id_customer`, `status`, `date_created`.

Tabla de **auditoría** opcional (`customer_field_update_audits`) si se requieren cambios de estado detallados (mismo patrón que `collection_audits`).

---

## Consumo vascorp / vascopro

Mismo patrón que sync de clientes y estados de cuenta:

1. **GET** `/v2/sync/customer-field-updates?status=pending` — lista pendientes (máx. 500).
2. vascopro actualiza vascorp (celular, flags, etc.).
3. **POST** `/v2/sync/customer-field-updates/ack` — `synced` | `rejected` por fila.
4. Opcional: próximo `customers-bulk` trae el maestro ya corregido.

Payload mínimo por ítem: `id`, `external_id` / `doc_type` + `doc_number`, `phone_e164`, `whatsapp_consent`, `managed_at`, `seller_username`.

Documentar contrato en `postman/VASCORP_SYNC_FIELD_UPDATES.md` y guía de implementación en `docs/VASCORP_IMPLEMENTAR_GESTION_CLIENTE.md`.

---

## UX en visita (borrador)

- Ruta: `/visit/manage` (o `/visit/customer/manage`).
- Layout: igual que cobranzas — cabecera + tarjeta resumen cliente a la izquierda.
- Wizard 3–4 pasos; mobile-first.
- Al guardar: toast de éxito + badge en dashboard «Gestionado» / «Pendiente sync» según `status` del último registro.
- Si el cliente ya tiene gestión `pending` reciente, mostrar aviso antes de crear otra (o marcar `superseded` la anterior).

---

## Orden de implementación sugerido

| # | Entrega | Depende de |
|---|---------|------------|
| 1 | Migración `customer_field_updates` + auditoría | — |
| 2 | Service + ajax guardar gestión en visita | Visita activa |
| 3 | Pantalla «Gestionar» + tile en dashboard cliente | 1–2 |
| 4 | API v2 GET/ack para vascopro | 1 |
| 5 | Panel supervisión: clientes sin gestionar / pendientes sync | 3–4 |
| 6 | Portal: usar `portal_account_requested` al activar fase 2 | Portal cliente |

**Después** de 1–3, la cola `customer_contact_pending` del doc de cobranzas puede **fusionarse** en esta tabla (un solo concepto de bandeja).

---

## Reglas de negocio

- Gestión requiere **visita activa** (mismo criterio que cobrar).
- Celular: misma validación que cobranzas (`vasco_validate_mobile_pe`).
- **No sobrescribir** `customers.phone_customer` al guardar gestión.
- vascorp es fuente de verdad del maestro; la bandeja Vasco no se borra por un bulk que no incluya esos cambios.
- Consentimiento WhatsApp: registrar boolean + timestamp; útil para cumplimiento antes de Evolution API.
- RBAC: permiso dedicado (ej. `visit_gestionar_cliente`) — definir en implementación.

---

## Referencias

- Celular y notificaciones en cobro: `docs/COBRANZA_NOTIFICACIONES.md`
- Visita vendedor: `docs/VISITA_VENDEDOR_CHECKLIST.md`
- Producto y portal fase 2: `docs/PRODUCTO.md`
- Validación teléfono: `api/helpers/phone.php`
- Sync clientes: `postman/VASCORP_SYNC.md`
