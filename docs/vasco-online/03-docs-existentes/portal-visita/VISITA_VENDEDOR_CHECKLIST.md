# Visita de vendedor — Checklist de implementación

**Objetivo:** que el vendedor, tras iniciar sesión, elija un cliente y entre en una **sesión operativa** con la información de ese cliente únicamente, para consultar estado de cuenta y (en siguientes pasos) cobrar y notificar.

**Issue:** `#15-feat: dashboard operativo del cliente para visitas de vendedor`

**Rutas admin:** `/visit` (buscar) · `/visit/customer` (dashboard) · `/visit/account-statement` (estado de cuenta)

**Referencia UI (mockups estáticos):** `admin/public/mockups/visita/`

| Pantalla | Mockup | Implementado en admin |
|----------|--------|------------------------|
| Buscar cliente | `index.html` | `/visit` |
| Dashboard del cliente | `cliente.html` | `/visit/customer` |
| Grupo empresarial | `grupo.html` | Pendiente |
| Estado de cuenta | `estado-cuenta.html` | `/visit/account-statement` |

---

## Estado actual (actualizado 16/06/2026)

### Hecho

- [x] Mockups mobile-first con datos ficticios (`admin/public/mockups/visita/`)
- [x] Módulo **Atender cliente** en admin con plantilla estándar (menú, navbar, Bootstrap)
- [x] Rutas `visit` y `visit/customer` en `Router.php`
- [x] Vistas en `views/pages/visit/`, CSS `public/customs/css/visit/`, JS `public/customs/js/visit/`
- [x] Sesión de visita en PHP (`VisitSessionHelper`, `VisitController`): iniciar al elegir cliente, terminar con **Terminar atención**
- [x] Log al iniciar/cerrar visita (`trace_id`, usuario, cliente) vía `error_log`
- [x] Búsqueda de clientes con datos reales (`VisitService`, `ajax/visit/search-customers.php`)
- [x] Búsqueda OR por código, nombre, documento o nombre comercial (fix API multi-campo)
- [x] Listado: badge **Al día** / **Con deuda** (sin montos en lista)
- [x] Dashboard con datos del cliente, resumen de cuenta (`customer_account_summaries`) y banner de grupo si aplica
- [x] Acciones deshabilitadas con “Próximamente”; **Terminar atención** en grid de acciones
- [x] UI responsive mobile / tablet / desktop; colores con clases Bootstrap del tema (`bg-label-*`, `bg-primary`, etc.)
- [x] Pantalla **estado de cuenta** en `/visit/account-statement` (local + consolidado de grupo)
- [x] Acción **Estados de cuenta** activa en el dashboard
- [x] `VisitService::getAccountStatementContext()` con documentos pendientes y filtros en cliente

### En progreso / siguiente

- [ ] Pantalla **grupo empresarial** (consolidado, cambio de local)
- [ ] Activar acción **Cobrar** en el dashboard y desde estado de cuenta
- [x] Permisos RBAC (`visitas_iniciar`, `visitas_estado_cuenta`, `visitas_grupo`, `visitas_cobrar`, `visitas_cobranzas_listar`) — migración `0014`
- [ ] Filtro por clientes asignados al vendedor (pendiente reorganización de vendedores)
- [ ] QR del cliente (fase 1.1)

---

## Paso 1 — Definición funcional y permisos

- [x] Nombre del módulo en menú: **Atender cliente** (ruta `visit`)
- [x] Definir permisos RBAC (`visitas_iniciar`, `visitas_estado_cuenta`, `visitas_grupo`, `visitas_cobrar`, `visitas_cobranzas_listar`)
- [ ] Regla: vendedor solo ve sus clientes asignados — **pendiente** (hoy lista todos los activos)
- [x] Cerrar visita → vuelve a `/visit` y limpia sesión
- [x] QR documentado como fase 1.1 (nice to have)

---

## Paso 2 — Sesión de visita (backend + admin)

- [x] Sesión activa en `$_SESSION['vasco_visit']` (cliente, inicio, origen, trace_id)
- [x] Iniciar visita al POST seleccionar cliente
- [x] Cerrar visita con `visit_action=end` (**Terminar atención**)
- [x] Guard: `/visit/customer` y `/visit/account-statement` exigen sesión activa
- [x] Logging al iniciar/cerrar
- [ ] Auditoría en tabla dedicada (hoy solo log de aplicación)
- [ ] Validar ownership vendedor ↔ cliente (cuando exista asignación)

---

## Paso 3 — Rutas y layout en admin

- [x] Rutas `visit`, `visit/customer` y `visit/account-statement` registradas
- [x] Vistas PHP bajo `views/pages/visit/`
- [x] Layout con plantilla admin existente (mobile-first + responsive)
- [x] CSS en `public/customs/css/visit/visit.css`
- [x] JS en `public/customs/js/visit/visit-search.js` y `visit-account.js`
- [x] Assets condicionados en `template.php`
- [ ] Ruta adicional: `visit/group`

---

## Paso 4 — Pantalla: buscar cliente

- [x] Búsqueda por código, nombre o documento (debounce 300 ms)
- [x] Listado con badge Al día / Con deuda y grupo si aplica
- [x] Ajax `admin/ajax/visit/search-customers.php`
- [x] Al seleccionar → iniciar sesión → `/visit/customer`
- [x] Estados vacío / sin resultados / error
- [x] Link **Continuar visita** si hay sesión activa
- [ ] Filtro por clientes del vendedor
- [ ] (Fase 1.1) Escanear QR

---

## Paso 5 — Pantalla: dashboard del cliente

- [x] Datos del cliente (código, nombre, documento, dirección)
- [x] Resumen: deuda total, vencido, docs pendientes, última sync
- [x] Banner de grupo empresarial (solo informativo; sin pantalla de detalle aún)
- [x] Grid de acciones con Próximamente en cobros, pedidos, catálogo
- [x] **Terminar atención** en acciones (no “cerrar sesión” de cuenta)
- [x] Botón **Buscar otro cliente**
- [x] Activar **Estados de cuenta** → `/visit/account-statement`
- [ ] Activar **Cobrar** → paso 10

---

## Paso 6 — Pantalla: grupo empresarial

- [ ] Vista dedicada (mockup `grupo.html`)
- [ ] Resumen consolidado y listado de locales
- [ ] Cambiar local atendido
- [ ] Link a estado de cuenta consolidado (desde pantalla grupo; hoy vía toggle en `/visit/account-statement?scope=group`)

---

## Paso 7 — Pantalla: estado de cuenta

- [x] Ruta `/visit/account-statement` con guard de sesión de visita
- [x] Resumen debe / vencido / sync (`VisitService::getAccountStatementContext`)
- [x] Documentos pendientes con vencidos resaltados (reutiliza estilos `account-statements.css`)
- [x] Filtro Todos / Vencidos (`visit-account.js`)
- [x] Toggle Este local / Todo el grupo (`?scope=group`)
- [x] Lógica alineada con `account-statements/detail.php` (mismos campos y orden)
- [ ] Botón Cobrar activo (enlace futuro; hoy deshabilitado con Próximamente)

---

## Paso 8 — API REST (opcional)

- [ ] Endpoints `/v1/visit/*` si se requiere fuera del admin
- [x] Por ahora: `admin/ajax/visit/` + `ApiService` interno

---

## Paso 9 — QR del cliente (fase 1.1)

- [ ] Payload, generación, lectura cámara, origen `qr` en sesión

---

## Paso 10 — Cobranzas desde la visita

- [ ] Flujo cobranza con cliente preseleccionado (mockup: `admin/public/mockups/visita/cobrar.html`)
- [ ] Solo **efectivo** entregado al vendedor (sin imputar documentos en campo)
- [ ] Ticket virtual (`TKT-*` en BD; **preview UI oculto** — ver `VisitCollectUiHelper` y `docs/vasco-online/03-docs-existentes/cobranzas/COBRANZA_TICKET_IMAGEN.md`)
- [ ] Notificación WhatsApp (texto)
- [ ] **Ticket como imagen** (post-MVP): PNG en `storage/`, descarga/compartir y adjunto WhatsApp — plan: `docs/vasco-online/03-docs-existentes/cobranzas/COBRANZA_TICKET_IMAGEN.md`
- [ ] Auditoría de cobranza
- [x] Rendición / validación de efectivo pendiente en empresa (API v2 GET/POST — ver `docs/vasco-online/03-docs-existentes/implementacion-vascorp/VASCORP_IMPLEMENTAR_RENDICION_COBRANZAS.md`)

---

## Paso 10b — Gestionar cliente en visita

Ver `docs/vasco-online/03-docs-existentes/portal-visita/GESTION_CLIENTE_VISITA.md`.

- [ ] Tile **Gestionar** en dashboard del cliente (`/visit/customer`)
- [ ] Tabla `customer_field_updates` (bandeja Vasco; no pisa maestro vascorp)
- [ ] Wizard: celular WhatsApp, autorización, marcar cliente gestionado
- [ ] API v2 GET/ack para vascopro
- [ ] Solicitud cuenta portal (cuando exista fase 2)

---

## Paso 11 — QA y cierre

- [x] Prueba básica mobile (listado, búsqueda, dashboard)
- [ ] Cliente sin grupo vs con grupo (pantalla grupo)
- [ ] Cliente sin deuda
- [ ] Permisos y ownership vendedor
- [ ] Performance con muchos clientes
- [ ] Actualizar `docs/vasco-online/03-docs-existentes/producto/CHANGELOG.md` por hito

---

## Orden recomendado de entrega (actualizado)

| # | Entrega | Estado |
|---|---------|--------|
| 1 | Mockups + checklist | ✅ Hecho |
| 2 | Sesión + buscar cliente + dashboard | ✅ Hecho |
| 3 | **Estado de cuenta en visita** | ✅ Hecho |
| 4 | Grupo empresarial en visita | 🔜 Siguiente |
| 5 | Permisos RBAC visita + rol Vendedor | ✅ Hecho (`0014`) |
| 6 | Cobrar desde visita (efectivo + ticket virtual) | Pendiente |
| 6b | Ticket virtual como imagen + descarga/compartir PNG | Post-MVP — `docs/vasco-online/03-docs-existentes/cobranzas/COBRANZA_TICKET_IMAGEN.md` |
| 6c | **Gestionar cliente** (bandeja datos → vascorp) | Pendiente — ver `docs/vasco-online/03-docs-existentes/portal-visita/GESTION_CLIENTE_VISITA.md` |
| 7 | QR | Opcional |

---

## Criterio de “hecho” para el primer release

El vendedor puede:

1. Buscar un cliente — ✅
2. Entrar al dashboard de ese cliente — ✅
3. Ver estado de cuenta (local y consolidado si hay grupo) — ✅
4. Cerrar la sesión y volver a buscar otro cliente — ✅

---

## Archivos clave (implementación)

| Área | Ruta |
|------|------|
| Sesión | `admin/src/Helpers/VisitSessionHelper.php` |
| Servicio | `admin/src/Services/VisitService.php` |
| Controller POST | `admin/src/Controllers/VisitController.php` |
| Vistas | `admin/views/pages/visit/` |
| Ajax búsqueda | `admin/ajax/visit/search-customers.php` |
| Estilos | `admin/public/customs/css/visit/visit.css` |
| JS búsqueda | `admin/public/customs/js/visit/visit-search.js` |
| JS estado de cuenta | `admin/public/customs/js/visit/visit-account.js` |
| Mockups cobranza | `admin/public/mockups/visita/cobrar.html`, `cobranza-exito.html`, `cobranzas.html` |
