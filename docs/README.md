# Documentación Vasco — índice por módulo

Mapa de **qué leer según tu rol**. Los contratos HTTP v2 viven en `postman/`; las guías de implementación en `docs/`.

---

## Producto y visión

| Documento | Para quién | Contenido |
|-----------|------------|-----------|
| [`PRODUCTO.md`](PRODUCTO.md) | Todo el equipo | Fases, actores, arquitectura monorepo, integraciones |
| [`CHANGELOG.md`](CHANGELOG.md) | Dev / release | Cambios por versión |

---

## Portal cliente (fase 2)

| Documento | Para quién | Contenido |
|-----------|------------|-----------|
| [`PORTAL_CLIENTE.md`](PORTAL_CLIENTE.md) | Producto + frontend admin | Rutas `/my-account`, UX, contraseña, **notificaciones cobranzas** |
| [`VASCORP_IMPLEMENTAR_SOLICITUDES_VISITA_PORTAL.md`](VASCORP_IMPLEMENTAR_SOLICITUDES_VISITA_PORTAL.md) | **vascorp** (job) | Consumir solicitudes de visita del portal |
| [`../postman/VASCORP_SYNC_PORTAL_VISIT_REQUESTS.md`](../postman/VASCORP_SYNC_PORTAL_VISIT_REQUESTS.md) | **vascorp** + QA | Contrato HTTP GET/ack solicitudes visita |

---

## Visita del vendedor (admin)

| Documento | Para quién | Contenido |
|-----------|------------|-----------|
| [`VISITA_VENDEDOR_CHECKLIST.md`](VISITA_VENDEDOR_CHECKLIST.md) | Frontend + QA | Checklist pantallas visita |
| [`GESTION_CLIENTE_VISITA.md`](GESTION_CLIENTE_VISITA.md) | Producto | Tile Gestionar, bandeja field updates |
| [`VASCORP_IMPLEMENTAR_GESTION_CLIENTE.md`](VASCORP_IMPLEMENTAR_GESTION_CLIENTE.md) | **vascorp** | Sync gestión en visita (celular, WhatsApp) |
| [`../postman/VASCORP_SYNC_FIELD_UPDATES.md`](../postman/VASCORP_SYNC_FIELD_UPDATES.md) | **vascorp** + QA | Contrato HTTP field updates |

---

## Cobranzas

| Documento | Para quién | Contenido |
|-----------|------------|-----------|
| [`COBRANZA_NOTIFICACIONES.md`](COBRANZA_NOTIFICACIONES.md) | Producto + backend | WhatsApp, portal, fases notificación |
| [`COBRANZA_TICKET_IMAGEN.md`](COBRANZA_TICKET_IMAGEN.md) | Frontend + backend | Ticket PNG, compartir, Evolution API |
| [`VASCORP_IMPLEMENTAR_RENDICION_COBRANZAS.md`](VASCORP_IMPLEMENTAR_RENDICION_COBRANZAS.md) | **vascorp** | Rendición efectivo cobrado en campo |
| [`VASCOPRO_SYNC_RENDICION_COBRANZAS.md`](VASCOPRO_SYNC_RENDICION_COBRANZAS.md) | **vascopro** | Guía rendición desde vascopro |
| [`../postman/VASCORP_SYNC_COLLECTIONS_DELIVER.md`](../postman/VASCORP_SYNC_COLLECTIONS_DELIVER.md) | **vascorp** + QA | Contrato HTTP collections-deliver |

---

## Maestros y estados de cuenta (sync vascorp → Vasco)

| Documento | Para quién | Contenido |
|-----------|------------|-----------|
| [`VASCORP_IMPLEMENTAR_ESTADOS_CUENTA.md`](VASCORP_IMPLEMENTAR_ESTADOS_CUENTA.md) | **vascorp** | Job estados de cuenta |
| [`VASCOPRO_SYNC_ESTADO_CUENTA.md`](VASCOPRO_SYNC_ESTADO_CUENTA.md) | **vascopro** | Sync desde vascopro |
| [`../postman/VASCORP_SYNC.md`](../postman/VASCORP_SYNC.md) | **vascorp** + QA | Contrato `customers-bulk` |
| [`../postman/VASCORP_SYNC_ACCOUNT.md`](../postman/VASCORP_SYNC_ACCOUNT.md) | **vascorp** + QA | Contrato `account-statements-bulk` |

---

## Mi gestión y supervisión (vendedor)

| Documento | Para quién | Contenido |
|-----------|------------|-----------|
| [`MI_GESTION_VENDEDOR_CHECKLIST.md`](MI_GESTION_VENDEDOR_CHECKLIST.md) | Frontend + QA | Módulo `/my-management` |
| [`SUPERVISION_VENDEDORES.md`](SUPERVISION_VENDEDORES.md) | Producto | Supervisión (futuro) |

---

## Postman y pruebas API

| Recurso | Para quién |
|---------|------------|
| [`../postman/README.md`](../postman/README.md) | Cualquier dev — importar colección, variables, flujo |
| [`../postman/vasco-api.postman_collection.json`](../postman/vasco-api.postman_collection.json) | Requests listos |

---

## Regla rápida

| Si eres… | Empieza por… |
|----------|----------------|
| **vascorp** (integración ERP) | `postman/VASCORP_SYNC*.md` + `docs/VASCORP_IMPLEMENTAR_*.md` |
| **Frontend admin / portal** | `PORTAL_CLIENTE.md`, `VISITA_VENDEDOR_CHECKLIST.md` |
| **Producto / arquitectura** | `PRODUCTO.md` |
| **Nuevo en el repo** | Este índice → módulo que toques |
