# Portal cliente — producto y roadmap

**Ubicación:** mismo admin (`admin.vasco.io`), rutas `/my-account/*`. Rol **Cliente portal** (`code_rol` 04).

Documento vivo: decisiones de UX, seguridad y entregas pendientes de fase 2.

---

## Estado actual (implementado)

| Ruta | Función |
|------|---------|
| `/my-account` | Inicio: deuda, tiles de consulta |
| `/my-account/account-statement` | Estado de cuenta (propio o local del grupo con `?local=`) |
| `/my-account/collections` | Historial de cobranzas en efectivo |
| `/my-account/collection` | Detalle de cobranza + compartir ticket |
| `/my-account/group` | Grupo empresarial (solo si el cliente pertenece a uno) |
| `/my-account/change-password` | Cambio de clave con validación segura |
| `/my-account/request-visit` | Solicitud de visita / apoyo del vendedor |

**Shell mobile:** sin sidebar admin, navbar reducida, navegación inferior (Inicio, Cuenta, Cobranzas, Grupo si aplica).

**RBAC:** módulo 09 — acciones `portal_*` en `migrations/0022`–`0024`.

---

## Contraseña inicial vs cambio obligatorio

### Decisión (etapa adopción — 2026)

**No forzar** el cambio de contraseña al primer login mientras los clientes se familiarizan con el portal.

- Clave inicial = **RUC/DNI** (`password_mode = doc_number` en `customer_portal_accounts`).
- Tile **«Cambiar contraseña»** con badge **Recomendado** si aún usan la clave por documento.
- Validación segura al cambiar (mín. 7 caracteres, mayúscula, minúscula, número, símbolo).

### Campaña futura (job / comunicación)

Más adelante, ejecutar una **campaña activa** para que todos cambien la clave:

| Objetivo | Cómo medirlo |
|----------|----------------|
| Seguridad | Menos cuentas con `password_mode = doc_number` |
| Adopción | Quién entró y completó el cambio (`password_mode → custom`) |
| Engagement | Base para recordatorios WhatsApp / correo a quien no cambió |

**Implementación sugerida (futuro):**

1. Reporte o job: clientes portal con `password_mode = doc_number` y último login.
2. Mensaje en portal (banner) + opcional bloqueo suave tras fecha límite.
3. Solo entonces valorar redirección obligatoria estilo futzoe (`must_change_password`).

**Por qué tiene sentido ahora:** en adopción temprana, un bloqueo al entrar aumenta fricción y abandono; la campaña posterior da señal clara de uso real.

---

## Grupo empresarial en navegación

- Tile y banner de grupo **solo** si `VisitService::getGroupContext` devuelve datos.
- Pestaña **Grupo** en la barra inferior **solo** con permiso `portal_grupo` **y** grupo asignado.
- Sin grupo → el cliente no ve opciones huérfanas que redirigen al inicio.

---

## Notificaciones en portal (pendiente — prioridad alta)

Canal persistente complementario a WhatsApp (`docs/COBRANZA_NOTIFICACIONES.md`, fase 2.0).

| Entrega | Detalle |
|---------|---------|
| Badge «nuevo» | Cobranzas no vistas desde última visita |
| Tabla lectura | Ej. `customer_portal_notification_reads` o `date_read` por cobranza |
| Inicio | Contador en tile Cobranzas o campana en navbar portal |
| Alcance MVP | Solo cobranzas registradas; facturas cuando exista módulo |

**No bloquea** operación actual; conviene después de estabilizar solicitud de visita y merge de la rama portal.

---

## Solicitud de visita (apoyo del vendedor)

El cliente puede pedir que lo visiten o lo contacten desde `/my-account/request-visit`.

| Campo | Uso |
|-------|-----|
| Cliente / usuario portal | Quién solicita |
| Mensaje opcional | Motivo breve (máx. 500 caracteres) |
| `status` | `pending` → `acknowledged` → `completed` (o `cancelled` si rechazada) |
| `trace_id` | Auditoría en logs |

**Reglas MVP:**

- Una solicitud **pending** por cliente; si ya existe, se muestra estado y no se duplica.
- Registro en tabla `customer_portal_visit_requests` (migración `0024`, ack vascorp `0025`).

### API v2 para vascorp

| Método | Ruta | Uso |
|--------|------|-----|
| `GET` | `/v2/sync/portal-visit-requests` | Listar solicitudes (`?status=pending`) |
| `POST` | `/v2/sync/portal-visit-requests/ack` | Marcar `acknowledged`, `completed` o `rejected` |

| Documento | Audiencia |
|-----------|-----------|
| [`postman/VASCORP_SYNC_PORTAL_VISIT_REQUESTS.md`](../postman/VASCORP_SYNC_PORTAL_VISIT_REQUESTS.md) | Contrato HTTP |
| [`VASCORP_IMPLEMENTAR_SOLICITUDES_VISITA_PORTAL.md`](VASCORP_IMPLEMENTAR_SOLICITUDES_VISITA_PORTAL.md) | Job en vascorp |

Índice general: [`docs/README.md`](README.md).

---

## Pendiente explícito (no iniciar aún)

| Ítem | Notas |
|------|--------|
| Merge PR rama portal | Esperar validación del equipo |
| Facturas (solo lectura) | Depende de sync/API facturas desde vascorp |
| Pedidos / catálogo | Fase 3 (`PRODUCTO.md`) |
| OTP en login | Roadmap autenticación |
| Host `cliente.vasco.io` | Mismo código; DNS + Traefik aparte |
| Ticket imagen PNG | Fase 1 post-MVP (`COBRANZA_TICKET_IMAGEN.md`) |

---

## Referencias

- Producto general: `docs/PRODUCTO.md`
- Notificaciones cobranza: `docs/COBRANZA_NOTIFICACIONES.md`
- Cuentas portal: `migrations/0021_customer_portal_accounts.sql`
- Provisionamiento desde visita: `docs/GESTION_CLIENTE_VISITA.md`
