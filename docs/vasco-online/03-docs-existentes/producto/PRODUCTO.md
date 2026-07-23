# Vasco — Producto y fases

## Qué es

**Vasco** es la plataforma en la nube (Hetzner) para que la empresa y sus clientes finales tengan **visibilidad en tiempo real** de la relación comercial, empezando por **cobranzas**.

**Vascopro** es la evolución del ERP interno **vascorp** (sin salida a internet). Vascorp **alimenta a Vasco vía API**: clientes, estados de cuenta, facturas consultables, etc.

## Problema que resolvemos primero

Los clientes entregan dinero a los vendedores (efectivo, confianza, seguridad, tiempo). A veces el vendedor no registraba a tiempo y la empresa se enteraba tarde porque todo era comunicación directa cliente–vendedor.

**MVP = cobranzas con trazabilidad y auditoría**, notificaciones en sistema y WhatsApp (Evolution API en pruebas).

## Arquitectura del monorepo

| Ruta | Rol |
|------|-----|
| `admin/` | Panel operativo (vendedores, supervisión) — Mobile-first |
| `api/` | REST consumida por admin, vascopro y futuro portal cliente |
| `storage/` | Archivos públicos (fotos de transferencias, comprobantes, etc.) |

Sin `store/` por ahora: el portal informativo del cliente llegará en otra fase.

## Actores y roles

- **Vendedor** — registra cobranzas, atiende clientes (pedidos en fase posterior).
- **Cliente final** — consulta estado de cuenta, cobranzas, facturas; recibe notificaciones.
- **Roles adicionales** — gerente de ventas, administración, etc. (módulo RBAC estándar).
- Un **cliente puede tener varios vendedores**; rotación controlada por roles.

## Integraciones

| Sistema | Relación |
|---------|----------|
| **vascorp / vascopro** | Origen de datos maestros; sincroniza clientes y estados de cuenta hacia Vasco API |
| **Evolution API** | WhatsApp para notificar al cliente (varios números registrados) |
| **json.pe** | Consulta DNI/RUC (opcional) |

**Gestión en visita (planificado):** tile **Gestionar** en la ficha del cliente — bandeja Vasco de datos capturados en campo (celular, autorización, cuenta portal) para consumo opcional de vascorp, sin pisar el maestro. Ver `docs/vasco-online/03-docs-existentes/portal-visita/GESTION_CLIENTE_VISITA.md`.

## Fases planificadas

### Fase 1 — MVP Cobranzas (actual)

Orden de implementación en el monorepo:

1. Maestros y sync vascorp (clientes, grupos, estados de cuenta)
2. Consulta operativa en admin (deuda, vencido, documentos pendientes)
3. Registro de cobranzas en campo (**solo efectivo** entregado al vendedor; la imputación a documentos la hace la empresa en vascorp, a la cuenta más antigua)
4. Ticket virtual por cobranza (código `TKT-*`) y, opcionalmente, código de ticket físico si se entrega recibo en papel
5. Notificación sistema + WhatsApp
6. **Ticket como imagen** (post-MVP, no bloquea cobro): generar PNG del ticket virtual, guardarlo en `storage/` y usarlo para descarga/compartir en admin y adjunto WhatsApp — **plan técnico:** `docs/vasco-online/03-docs-existentes/cobranzas/COBRANZA_TICKET_IMAGEN.md` (mockup: `admin/public/mockups/visita/cobranza-exito.html`)
7. Auditoría obligatoria
8. Validación / supervisión vía API (efectivo pendiente de entrega a la empresa) — `GET/POST /v2/sync/collections-*` — ver `docs/vasco-online/03-docs-existentes/implementacion-vascorp/VASCORP_IMPLEMENTAR_RENDICION_COBRANZAS.md`
9. Consulta de facturas (solo lectura), cuando aplique

**Registro de cobranza (efectivo)** — alcance acordado:

| En campo (vendedor) | En empresa (después) |
|---------------------|----------------------|
| Monto recibido en efectivo | Imputación a la cuenta más antigua |
| Ticket virtual + código físico opcional | Validación / rendición de efectivo |
| Notificación al cliente | Sincronización con vascorp si aplica |

**Ticket virtual → imagen** (post-MVP; ver `docs/vasco-online/03-docs-existentes/cobranzas/COBRANZA_TICKET_IMAGEN.md`):

- Tras registrar la cobranza, renderizar el ticket (datos + QR con código de cobranza/ticket) como imagen.
- Persistir en `storage/` (misma línea que comprobantes de transferencia).
- Enviar adjunto vía Evolution API (WhatsApp) a los números del cliente y/o acción «Compartir» / «Descargar» en el admin (Web Share API o descarga directa).
- Referencia de UI: mockup visita — pantalla de éxito y botón «Compartir ticket».
- **MVP actual:** solo ticket HTML + compartir texto; la columna `ticket_image_path_collection` ya está en BD para cuando se implemente.

- Entorno de desarrollo y despliegue Hetzner
- API + admin base (JWT como futzoe/aida)

### Fase 2 — Portal cliente

- Panel informativo mobile-first (estado de cuenta, cobranzas, facturas)
- Login cliente: RUC/DNI + clave; OTP y soporte continuo
- **Detalle operativo y roadmap:** `docs/vasco-online/03-docs-existentes/portal-visita/PORTAL_CLIENTE.md`
- **Índice documentación por módulo:** `docs/README.md`

### Fase 3 — Operación comercial

- Pedidos que el vendedor atiende al cliente
- Catálogo, stock, descuentos (no MVP)

### Fase 4 — Multi-empresa (SaaS)

- Hoy: solo Vasco (un tenant)
- Diseño preparado para más empresas después

## Autenticación

- JWT HS256 (referencia: **futzoe** — validación de secret, encode seguro)
- API Key para integración **vascopro → vasco**
- Login usuarios: RUC/DNI + clave; OTP en roadmap

## Entornos

| Entorno | Admin | API | Storage |
|---------|-------|-----|---------|
| Desarrollo | `admin.vasco.io:8084` | `api.vasco.io:8084` | `storage.vasco.io:8084` |
| Producción | DNS real + Traefik TLS | Idem | Idem |

MySQL desarrollo en host: **3310**.

## Convenciones

- Idioma UI: **español**
- CSS propio prefijado `vasco-`
- Bootstrap 5 + JS vanilla
- Migraciones SQL versionadas en `migrations/`
- Sin Redis/WebSocket en esta etapa
