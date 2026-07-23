# Supervisión de vendedores — Planificado (fase posterior)

**Estado:** documentado, **no implementado**. El desarrollo actual es solo la vista del **vendedor** (`/my-management`).

**Objetivo (futuro):** que gerencia / supervisor de ventas evalúe el desempeño de cada vendedor con los mismos criterios que el vendedor ve en «Mi evaluación», pero agregado por persona y con comparativas de equipo.

---

## Criterio de evaluación (ya implementado para el vendedor)

Fuente de verdad: tabla `seller_visit_outcomes` (migración `0017` / reparación `0018`).

| Resultado (`outcome`) | ¿Cuenta como gestión válida? | Evidencia |
|----------------------|------------------------------|-----------|
| `collected` | Sí | Cobranza registrada en visita (`collections`) |
| `account_informed` | Sí | Vendedor confirmó que informó estado de cuenta |
| `both` | Sí | Cobró e informó cuenta |
| `no_action` | No | Cierre explícito sin gestión |

Complemento de auditoría (no KPI principal): `seller_customer_consultations` — visitas iniciadas (apertura de cliente).

---

## Pantallas propuestas (supervisor)

| Ruta propuesta | Rol | Contenido |
|----------------|-----|-----------|
| `/supervision` o `/sales-management` | Gerente / supervisor | Resumen equipo: gestión válida hoy/semana/mes, cobrado, sin gestión |
| `/supervision/seller/{id}` | Gerente / supervisor | Detalle de un vendedor (misma lógica que Mi evaluación) |
| `/supervision/ranking` | Opcional fase 2 | Ranking por cobrado / clientes con gestión (sin gamificación agresiva al inicio) |

**No confundir con:** `/my-management` — solo datos del usuario logueado.

---

## KPIs para gerencia (mismos datos, otra agregación)

Por vendedor y período (`today`, `week`, `month`, `30d`):

- **Clientes con gestión** — `COUNT(DISTINCT customer)` donde `outcome != no_action`
- **Cierres válidos** — filas con gestión válida
- **Cobrado en visitas** — `SUM(amount_collected)` donde outcome incluye cobro
- **Informó cuenta** — outcomes `account_informed` o `both`
- **Sin gestión** — `outcome = no_action`
- **Visitas iniciadas** — desde `seller_customer_consultations` (brecha vs gestión válida)

Reutilizar consultas de:

- `SellerVisitOutcomeRepository::summarizeBySeller()` — parametrizar `seller_id`
- `SellerConsultationRepository::summarizeBySeller()` — auditoría de aperturas

---

## RBAC propuesto (cuando se implemente)

Migración futura (ej. `0019_rbac_supervision_vendedores.sql`):

| Acción | Clave sugerida |
|--------|----------------|
| Ver panel supervisión | `supervision_ver` |
| Ver detalle de vendedor | `supervision_vendedor_detalle` |
| Ver todos los vendedores | `supervision_equipo` |

Roles: **Supervisor** (`02`), **Gerente de ventas**, **Adminsys** (`01`).  
**Vendedor** (`03`): sin acceso a supervisión.

---

## Backend propuesto

```
admin/src/Services/SellerSupervisionService.php   — agregados por equipo / vendedor
admin/src/Repositories/SellerVisitOutcomeRepository.php  — extender filtros por seller_id (ya existe)
admin/views/pages/supervision/                    — vistas supervisor
```

Sin duplicar lógica de negocio: el servicio de supervisión debe llamar a los mismos repositorios que `SellerManagementService` / `SellerVisitOutcomeService`.

---

## Orden de implementación sugerido

1. **Ahora (vendedor):** Mi gestión + evaluación + cierre de visita ✅ en curso
2. RBAC Mi gestión (`mi_gestion_*`)
3. Cartera asignada (`seller_user_id_customer` desde vascorp)
4. **Supervisión MVP:** listado de vendedores + KPIs por período + detalle (sin ranking)
5. Export CSV / API para reportes externos
6. Metas y comparativas (fase comercial posterior)

---

## Cómo verificar datos hoy (sin pantalla supervisor)

```sql
SELECT
  u.id_user,
  u.name_user,
  svo.outcome_seller_visit_outcome,
  COUNT(*) AS cierres,
  SUM(svo.amount_collected_seller_visit_outcome) AS cobrado
FROM seller_visit_outcomes svo
INNER JOIN users u ON u.id_user = svo.id_user_seller_visit_outcome
WHERE DATE(svo.date_created_seller_visit_outcome) = CURDATE()
GROUP BY u.id_user, u.name_user, svo.outcome_seller_visit_outcome;
```

---

## Referencias

- Checklist vendedor: `docs/vasco-online/03-docs-existentes/portal-visita/MI_GESTION_VENDEDOR_CHECKLIST.md`
- Visita y cierre: `docs/vasco-online/03-docs-existentes/portal-visita/VISITA_VENDEDOR_CHECKLIST.md`
- Migración outcomes: `migrations/0017_seller_visit_outcomes.sql`
