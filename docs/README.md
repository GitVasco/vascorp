# Documentación vascorp

Índice de `docs/` tras la reorganización.

---

## Vasco Online (integración con VascoPro)

| Carpeta | Uso |
|---------|-----|
| [`vasco-online/01-pedidos-a-vascopro/`](vasco-online/01-pedidos-a-vascopro/) | Pedidos que **vascorp** escribe para VascoPro |
| [`vasco-online/02-respuestas-vascopro/`](vasco-online/02-respuestas-vascopro/) | Respuestas/contratos que manda VascoPro (se copian aquí) |
| [`vasco-online/03-docs-existentes/`](vasco-online/03-docs-existentes/) | Docs Vasco ya existentes, ordenados por tema |

Flujo: pedido en `01` → respuesta en `02` → implementar en vascorp. Contexto histórico en `03`.

### Docs existentes (atajo)

| Tema | Carpeta |
|------|---------|
| Producto / changelog | [`03-docs-existentes/producto/`](vasco-online/03-docs-existentes/producto/) |
| Cobranzas / rendición | [`03-docs-existentes/cobranzas/`](vasco-online/03-docs-existentes/cobranzas/) |
| Portal y visita | [`03-docs-existentes/portal-visita/`](vasco-online/03-docs-existentes/portal-visita/) |
| Sync estados de cuenta | [`03-docs-existentes/sync-estados-cuenta/`](vasco-online/03-docs-existentes/sync-estados-cuenta/) |
| Handoffs implementación | [`03-docs-existentes/implementacion-vascorp/`](vasco-online/03-docs-existentes/implementacion-vascorp/) |

Contratos HTTP: [`../postman/`](../postman/).

---

## Comercial (interno vascorp)

Planes de módulos comerciales: [`comercial/`](comercial/).

| Carpeta | Tema |
|---------|------|
| [`metas-retos/`](comercial/metas-retos/) | Metas, retos, incentivos |
| [`zonas-marcas/`](comercial/zonas-marcas/) | Zonas y asignación de marcas |
| [`linea-credito/`](comercial/linea-credito/) | Línea de crédito / inteligencia |
| [`recetas-modelos/`](comercial/recetas-modelos/) | Recetas, ficha, costos, categorías |
| [`regularizaciones/`](comercial/regularizaciones/) | Regularizaciones comerciales |
| [`dashboard-cxc/`](comercial/dashboard-cxc/) | Dashboard CxC |

---

## Producción (interno vascorp)

| Doc | Tema |
|-----|------|
| [`produccion/REFACTOR_SECTORES_TALLER_SERVICIO.md`](produccion/REFACTOR_SECTORES_TALLER_SERVICIO.md) | Refactor `sectorjf`: taller (interno) vs servicio (externo) |

---

## TI (interno)

| Doc | Tema |
|-----|------|
| [`ti/PLAN_HELPDESK.md`](ti/PLAN_HELPDESK.md) | Helpdesk interno: tickets, estados e indicadores |

---

## SQL y tests

| Carpeta | Contenido |
|---------|-----------|
| [`sql/`](sql/) | Migraciones y scripts SQL |
| [`tests/`](tests/) | Tests de documentación / adaptadores |

---

## Regla rápida

| Si eres… | Empieza por… |
|----------|----------------|
| **Pedir algo a VascoPro** | `vasco-online/01-pedidos-a-vascopro/` |
| **Pegar respuesta de VascoPro** | `vasco-online/02-respuestas-vascopro/` |
| **Integrar ERP / sync** | `postman/VASCORP_SYNC*.md` + `vasco-online/03-docs-existentes/implementacion-vascorp/` |
| **Módulo comercial vascorp** | `comercial/` + `sql/` |
| **Nuevo en el repo** | Este índice |
