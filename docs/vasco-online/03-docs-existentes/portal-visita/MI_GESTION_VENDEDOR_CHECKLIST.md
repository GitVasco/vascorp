# Mi gestión (vendedor) — Checklist de implementación

**Objetivo:** que el vendedor vea **su desempeño operativo** sin depender de una visita activa: cuánto cobró, qué efectivo debe rendir, historial global y cartera con deuda.

**Alcance:** fase 1 (cobranzas). No incluye metas comerciales, ranking ni pedidos (fase 3).

**Rutas admin propuestas:**

| Ruta | Pantalla |
|------|----------|
| `/my-management` | Resumen (KPIs + accesos rápidos) |
| `/my-management/collections` | Mis cobranzas (historial global) |
| `/my-management/pending-delivery` | Por rendir (efectivo en custodia) |
| `/my-management/portfolio` | Mi cartera (clientes asignados + deuda) |

**Relación con otros módulos:**

| Módulo | Diferencia |
|--------|------------|
| **Atender cliente** (`/visit`) | Flujo cliente a cliente; cobranzas por cliente en sesión |
| **Mi gestión** | Vista agregada del vendedor logueado; no requiere visita activa |
| **Gestionar cliente** (futuro) | Wizard de adopción en visita — ver `docs/vasco-online/03-docs-existentes/portal-visita/GESTION_CLIENTE_VISITA.md` |

**Datos ya disponibles en BD:**

- `collections.id_user_seller_collection`, `status_collection` (`pending_delivery` \| `delivered` \| `cancelled`)
- `customers.seller_user_id_customer` (vendedor principal desde vascorp)
- `customer_account_summaries` (deuda total, vencido, docs pendientes)

**Referencia UI a reutilizar:** listado y filtros de `admin/views/pages/visit/actions/collections.php` + `visit-collections.js`

---

## Estado actual

### Hecho

- [x] Cobranzas registradas con vendedor (`collections`, migración `0012`)
- [x] Historial de cobranzas **por cliente en visita** (`/visit/collections`)
- [x] API v2 rendición por vendedor (`GET /v2/sync/collections-pending-delivery?seller_user_id=`)
- [x] Maestro clientes con `seller_user_id_customer` (sync vascorp)
- [x] **PR1 (sin RBAC aún):** ruta `/my-management`, menú, `SellerManagementService`, KPIs reales + actividad reciente
- [x] **PR2 (sin RBAC aún):** `/my-management/collections` + detalle `/my-management/collection`
- [x] **PR3 (sin RBAC aún):** `/my-management/pending-delivery`
- [x] **PR4 (sin RBAC aún):** historial consultas — migración `0015`, log al iniciar visita
- [x] **Evaluación por gestión válida:** migración `0017`/`0018`, `seller_visit_outcomes`, cierre de visita con outcome, `/my-management/consultations` → **Mi evaluación**

### Pendiente (este módulo)

- [ ] RBAC migración `0017`+ (al final; ver acciones `mi_gestion_*`)
- [ ] **Supervisión gerencia** — documentado en `docs/vasco-online/03-docs-existentes/portal-visita/SUPERVISION_VENDEDORES.md` (implementación posterior)
- [ ] Resto del checklist de abajo

---

## Paso 1 — Definición funcional y permisos

- [ ] Nombre en menú: **Mi gestión** (ruta `my-management`)
- [ ] Módulo RBAC nuevo (migración `0016`): código `08`, nombre «Mi gestión vendedor»
- [ ] Acciones RBAC:
  - [ ] `mi_gestion_ver` — entrar al resumen
  - [ ] `mi_gestion_cobranzas` — historial global de cobranzas
  - [ ] `mi_gestion_por_rendir` — efectivo pendiente de entrega
  - [ ] `mi_gestion_cartera` — clientes asignados con deuda
- [ ] Asignar permisos a roles **Vendedor** (`03`), **Supervisor** (`02`), **Adminsys** (`01`)
- [ ] Regla de datos: el vendedor solo ve **sus** cobranzas (`id_user_seller_collection` = usuario logueado)
- [ ] Regla de cartera: solo clientes con `seller_user_id_customer` = usuario logueado (cuando vascorp asigne; hoy puede estar vacío)
- [ ] Supervisor / gerente: ver gestión de **otro** vendedor — **fuera de MVP** (fase supervisión)

---

## Paso 2 — Backend (repositorio y servicio)

### `CollectionRepository` (extender)

- [ ] `listBySeller(PDO, int $sellerId, ?string $status, int $limit, ?DateTimeInterface $since)` — listado global
- [ ] `summarizeBySeller(PDO, int $sellerId, int $days)` → `{ total_amount, pending_amount, delivered_amount, count, customers_count }`
- [ ] `countPendingDeliveryBySeller(PDO, int $sellerId)` — monto y cantidad por rendir
- [ ] Índice existente: `idx_collections_seller_status` — validar que cubre consultas

### `SellerManagementService` (nuevo en `admin/src/Services/`)

- [ ] `getDashboardSummary(int $sellerId)` — KPIs hoy / semana / mes
- [ ] `listMyCollections(int $sellerId, string $period, string $statusFilter)` — para listado UI
- [ ] `listPendingDelivery(int $sellerId)` — subset `pending_delivery`
- [ ] `mapCollectionRowForList(array $row)` — reutilizar formato de `CollectionService` (cliente, ticket, estado, monto)
- [ ] Enriquecer filas con datos de cliente vía `ApiService` o JOIN si se mueve lógica a API

### `PortfolioService` o método en `SellerManagementService`

- [ ] `listMyCustomersWithDebt(int $sellerId, int $limit)` — `customers` + `customer_account_summaries`
- [ ] Ordenar por `overdue_balance` DESC (o equivalente en tabla resumen)
- [ ] Link «Atender» → iniciar visita con ese cliente (`/visit` + POST selección)

### Ajax (admin)

- [ ] `admin/ajax/my-management/summary.php` — KPIs (opcional si todo es SSR en MVP)
- [ ] `admin/ajax/my-management/collections.php` — listado paginado/filtrado
- [ ] `admin/ajax/my-management/portfolio.php` — cartera (si se carga lazy)

### API REST (opcional fase 1)

- [ ] Endpoints `/v1/seller-management/*` — solo si se consume fuera del admin
- [ ] Por ahora: servicios PHP en admin + PDO (mismo patrón que cobranzas en visita)

---

## Paso 3 — Rutas y layout en admin

- [ ] Registrar `my-management` en `Router.php` (`allowedRoutes` + `routesWithActions`)
- [ ] Acciones: `collections`, `pending-delivery`, `portfolio`
- [ ] Vistas en `admin/views/pages/my-management/`
- [ ] `TemplateController`: títulos de página, permisos por ruta (helper `MyManagementPermissionsHelper`)
- [ ] Ítem de menú en `admin/views/modules/menu.php` (junto a **Atender cliente**)
- [ ] CSS `admin/public/customs/css/my-management/my-management.css` (prefijo `vasco-`)
- [ ] JS `admin/public/customs/js/my-management/` — filtros período/estado
- [ ] Assets condicionados en `template.php`
- [ ] **No** requiere sesión de visita activa (a diferencia de `/visit/customer`)

---

## Paso 4 — Pantalla: resumen (`/my-management`)

- [ ] Tarjetas KPI (mobile-first, 2 columnas en móvil):
  - [ ] **Cobrado hoy** (monto + # cobranzas)
  - [ ] **Cobrado semana** / **mes** (toggle o fila secundaria)
  - [ ] **Por rendir** (monto destacado — alerta visual si > 0)
  - [ ] **Deuda vencida cartera** (total + # clientes con vencido > 0)
- [ ] Accesos rápidos (tiles o lista):
  - [ ] Mis cobranzas
  - [ ] Por rendir
  - [ ] Mi cartera
- [ ] Bloque **Actividad reciente** (últimas 5 cobranzas con cliente y estado)
- [ ] Estados vacío: vendedor sin cobranzas / sin clientes asignados
- [ ] Mockup estático opcional: `admin/public/mockups/mi-gestion/index.html`

---

## Paso 5 — Pantalla: mis cobranzas (`/my-management/collections`)

- [ ] Listado global del vendedor (últimos 30 días por defecto; alineado con `CollectionService::HISTORY_DAYS`)
- [ ] Filtros: período (hoy, semana, mes, 30 días) y estado (todas, por rendir, rendidas)
- [ ] Cada ítem: fecha, cliente (código + nombre), monto, ticket, badge estado
- [ ] Tap → detalle de cobranza (reutilizar `visit/actions/collection.php` o vista compartida)
- [ ] Reutilizar estilos `vasco-visit-collect-history` donde aplique

---

## Paso 6 — Pantalla: por rendir (`/my-management/pending-delivery`)

- [ ] Solo `status_collection = pending_delivery`
- [ ] Total acumulado arriba (call-to-action informativo: «Entregar en caja/administración»)
- [ ] Lista con monto, cliente, fecha, código cobranza
- [ ] Sin acción de «marcar rendido» en admin vendedor (lo hace vascorp vía API v2 — ver `docs/vasco-online/03-docs-existentes/cobranzas/VASCOPRO_SYNC_RENDICION_COBRANZAS.md`)
- [ ] Actualización al volver a la pantalla (estado cambia cuando vascorp confirma `delivered`)

---

## Paso 7 — Pantalla: mi cartera (`/my-management/portfolio`)

- [ ] Clientes con `seller_user_id_customer` = vendedor logueado
- [ ] Columnas/tarjetas: código, nombre, deuda total, vencido, docs pendientes
- [ ] Badge Al día / Con deuda / Con vencido
- [ ] Acción **Atender** → `/visit` con preselección de cliente (o POST directo si ya existe helper)
- [ ] Orden por vencido descendente
- [ ] Mensaje si vascorp aún no asigna vendedores en sync

---

## Paso 8 — Detalle de cobranza (compartido)

- [ ] Ruta `/my-management/collection?id=` o `/my-management/collection/{id}` (definir una)
- [ ] Misma información que `/visit/collection` pero accesible sin visita activa
- [ ] Guard: la cobranza debe pertenecer al vendedor logueado (o 403)
- [ ] Botón volver según origen (gestión vs visita)

---

## Paso 9 — Seguridad y auditoría

- [ ] Validar `seller_id` siempre desde JWT/sesión — nunca desde query del cliente
- [ ] 403 si intenta ver cobranza de otro vendedor
- [ ] Logging con `trace_id` en consultas pesadas (opcional)
- [ ] Sin datos sensibles extra en listados (solo lo necesario para operación)

---

## Paso 10 — QA y cierre

- [ ] Vendedor con cobranzas hoy / sin cobranzas
- [ ] Cobranza `pending_delivery` vs `delivered` tras sync vascorp
- [ ] Vendedor sin clientes asignados en maestro
- [ ] Permisos: rol sin `mi_gestion_*` → 403
- [ ] Mobile: tarjetas, scroll, filtros táctiles
- [ ] Actualizar `docs/vasco-online/03-docs-existentes/producto/CHANGELOG.md` por hito
- [ ] Referencia cruzada en `docs/vasco-online/03-docs-existentes/producto/PRODUCTO.md` (opcional, una línea en fase 1)

---

## Fases posteriores (no MVP)

- [ ] Clientes **gestionados** (conteo desde `customer_field_updates` — `docs/vasco-online/03-docs-existentes/portal-visita/GESTION_CLIENTE_VISITA.md`)
- [ ] Vista supervisor: elegir vendedor y ver su gestión — **plan:** `docs/vasco-online/03-docs-existentes/portal-visita/SUPERVISION_VENDEDORES.md`
- [ ] Metas y comparativas
- [ ] Notificaciones push «Tienes S/ X por rendir»
- [ ] Export CSV para el vendedor

---

## Orden recomendado de entrega

| # | Entrega | Depende de |
|---|---------|------------|
| 1 | RBAC + rutas + menú + página resumen vacía | — |
| 2 | `CollectionRepository` + `SellerManagementService` (queries vendedor) | — |
| 3 | **Resumen con KPIs reales** | 1, 2 |
| 4 | **Mis cobranzas** (listado global) | 2, 3 |
| 5 | **Por rendir** | 2 (puede ser filtro de 4) |
| 6 | Detalle cobranza sin visita | 4 |
| 7 | **Mi cartera** | sync `seller_user_id` en clientes |
| 8 | QA + pulido mobile | todo |

---

## Criterio de «hecho» para MVP

El vendedor puede:

1. Entrar a **Mi gestión** desde el menú — sin visita activa
2. Ver cuánto cobró (hoy/semana) y cuánto **debe rendir**
3. Revisar el historial de **todas** sus cobranzas con filtros
4. Ver lista de efectivo **por rendir**
5. (Si hay asignación vascorp) Ver su cartera con deuda vencida y abrir visita

---

## Archivos clave (a crear / tocar)

| Área | Ruta |
|------|------|
| Checklist | `docs/vasco-online/03-docs-existentes/portal-visita/MI_GESTION_VENDEDOR_CHECKLIST.md` |
| Migración RBAC | `migrations/0015_rbac_mi_gestion_vendedor.sql` |
| Permisos | `admin/src/Helpers/MyManagementPermissionsHelper.php` |
| Servicio | `admin/src/Services/SellerManagementService.php` |
| Repositorio | `admin/src/Repositories/CollectionRepository.php` (extender) |
| Router | `admin/src/Router/Router.php` |
| Template / guards | `admin/src/Controllers/TemplateController.php` |
| Vistas | `admin/views/pages/my-management/` |
| Menú | `admin/views/modules/menu.php` |
| Estilos | `admin/public/customs/css/my-management/` |
| JS | `admin/public/customs/js/my-management/` |
| Referencia listado | `admin/views/pages/visit/actions/collections.php` |
| Referencia rendición | `docs/vasco-online/03-docs-existentes/cobranzas/VASCOPRO_SYNC_RENDICION_COBRANZAS.md` |

---

## Con qué empezar

**Orden acordado:** RBAC al final. Primero valor visible en pantalla.

**Entrega 1 (en curso):** pasos **2 + 3 + 4** del orden recomendado (sin paso 1 RBAC).

1. ~~Ruta **`/my-management`**, ítem de menú y vista resumen~~ ✅
2. ~~`summarizeBySeller` + `summarizePendingDeliveryBySeller` + `SellerManagementService`~~ ✅
3. ~~**Mis cobranzas** (`/my-management/collections`)~~ ✅
4. ~~**Por rendir** (`/my-management/pending-delivery`)~~ ✅
5. ~~**Historial de consultas**~~ ✅
6. **Siguiente:** **RBAC** (migración `0016`) + pulido
7. **Cartera** (cuando vascorp asigne vendedores)

**Entrega RBAC (al final):** migración `0016` + `MyManagementPermissionsHelper` + guards en menú y rutas.
