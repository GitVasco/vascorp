# Estado actual — Reportes generales v2

**Última actualización:** 2026-09-01  
**Pausado en:** Fase 0 completada · Fase 1 no iniciada  
**Motivo pausa:** bug urgente en otro módulo

Documento de handoff para retomar sin perder contexto.

---

## Resumen en una línea

Esqueleto v2 operativo en `/reportes-generales-v2` (catálogo, filtros dinámicos, vista previa con mensaje “en construcción”); **ningún reporte devuelve datos aún**. v1 legacy intacto.

---

## Qué está hecho

### Documentación (v1 inventariado)

| Archivo | Estado |
|---------|--------|
| `REPORTES_GENERALES_V1_CATALOGO_COMPLETO.md` | ✅ 16 reportes v1 + filtros + checklist migración |
| `REPORTES_GENERALES_V1_AUDIT.md` | ✅ qué funciona / falla en legacy |
| `PLAN_REPORTES_GENERALES_V2.md` | ✅ fases 0–4, UX Aida, equivalencias |

### Fase 0 — Esqueleto ✅

- Ruta registrada en `vistas/plantilla.php`
- Menú **Reportes generales v2 (beta)** visible solo para IDs en `ReportesGeneralesV2Config::idsAccesoBeta()` (actualmente **6**)
- Catálogo PHP: 16 plantillas, 4 grupos, filtros declarativos
- UI: sidebar plantillas + panel filtros + vista previa + Excel/PDF (deshabilitados)
- AJAX: `preview` / `catalogo` / `export` (export sin implementar)
- Todos los reportes en estado `pendiente` → SweetAlert “Reporte en construcción (Fase N)”

### Cambio menor en v1 (previo a v2)

Filtro **cliente** en reporte **Pagos** del legacy (`cuentas.js`, modelo, controlador, PDF, Excel). Commit sugerido (no commiteado en esta sesión):

```
feat(reportes-generales): filtrar el reporte de Pagos por cliente
```

### Fixes durante Fase 0

- **PHP 5.x:** reemplazado `??` por `isset()` en `reportes-generales-v2.controlador.php` (línea 53)
- **UI catálogo:** varias iteraciones; versión actual = icono + título + badge F1/F2/F3; hint solo en panel derecho al seleccionar

---

## Archivos del módulo v2

| Archivo | Rol |
|---------|-----|
| `controladores/reportes-generales-v2.config.php` | Catálogo único (ids, grupos, filtros, fases, estado) |
| `controladores/reportes-generales-v2.controlador.php` | Preview/export (stub) |
| `ajax/reportes-generales-v2.ajax.php` | API JSON |
| `vistas/modulos/cuentas-corrientes/reportes-generales-v2.php` | Vista principal |
| `vistas/js/reportes-generales-v2.js` | Catálogo, filtros, AJAX preview |
| `vistas/css/reportes-generales-v2.css` | Estilos sidebar/workspace |

**Tocados para integrar:** `vistas/plantilla.php`, `vistas/modulos/menu.php`, `docs/README.md`, `docs/cuentas-corrientes/*`

**Assets cache:** CSS/JS cargados con `?v=3` en plantilla.

---

## Cómo probar al retomar

1. Usuario **id 6** (lista beta en `reportes-generales-v2.config.php` → `idsAccesoBeta()`)
2. Menú → Cuentas corrientes → **Reportes generales v2 (beta)**
3. Elegir grupo (Cobranza / Letras / Movimientos / Pagos)
4. Clic en una plantilla → aparecen filtros según catálogo
5. **Vista previa** → mensaje “Reporte en construcción (Fase 1/2/3)”
6. Excel/PDF siguen deshabilitados
7. `/reportes-generales` (legacy) sin cambios de comportamiento salvo filtro cliente en Pagos

---

## Qué NO está hecho

| Ítem | Fase |
|------|------|
| Datos reales en vista previa | 1+ |
| Export Excel/PDF v2 | 1+ |
| Servicios/adaptadores reutilizando modelos v1 | 1+ |
| Ocultar filtros irrelevantes por plantilla (orden2, banco, etc.) | 1 (pulido) |
| 7 reportes prioritarios | 1 |
| Letras + cancelados | 2 |
| Agrupaciones + movimientos | 3 |
| Piloto y corte de menú legacy | 4 |

---

## Próximo paso al retomar (Fase 1)

Orden sugerido en [`PLAN_REPORTES_GENERALES_V2.md`](PLAN_REPORTES_GENERALES_V2.md):

1. Crear capa de servicio v2 (ej. `reportes-generales-v2.servicio.php`) que envuelva consultas v1 probadas
2. Implementar **`doc_por_cobrar`**: preview tabla + Excel + PDF + todos los filtros
3. Implementar **`pagos`** (ya tiene filtro cliente en v1 como referencia)
4. Seguir con vencidos, no vencidos, protestados, estado_cuenta, saldos_fecha
5. Por cada reporte: cambiar `estado` a `listo` en config + checklist manual del plan

**Regla:** no tocar v1 salvo bug crítico.

---

## Decisiones acordadas

- Ir **fase a fase**; avisar qué probar al cerrar cada fase
- v2 en paralelo; v1 congelado hasta corte de menú
- UX tipo **Aida**: catálogo → Vista previa → Excel/PDF (sin radio pantalla/Excel)
- `pagos_comisiones` (option16) = **fuera de alcance** inicial
- PHP del servidor: **sin `??`** ni sintaxis PHP 7+ en archivos v2

---

## Git (al retomar)

Cambios locales **sin commit** (según inicio de sesión). Al volver, revisar `git status` antes de commitear; conviene al menos un commit de Fase 0 + docs, separado del fix v1 de Pagos si aplica.

---

## Referencia rápida Aida

`/Users/joel/Proyectos/aida/admin` → `/reports`  
Patrón: `reports.config.js` + `reports.js` + `ajax/reports/*.php` + `export.php`
