# Cuentas corrientes (documentación interna)

Planes y auditorías de módulos de **Cuentas corrientes / Cobranzas** en vascorp.

| Documento | Contenido |
|-----------|-----------|
| [`ESTADO_REPORTES_GENERALES_V2.md`](ESTADO_REPORTES_GENERALES_V2.md) | **Handoff / estado actual** — dónde quedamos, qué probar, próximo paso |
| [`REPORTES_GENERALES_V1_CATALOGO_COMPLETO.md`](REPORTES_GENERALES_V1_CATALOGO_COMPLETO.md) | **Inventario exhaustivo** de los 16 reportes v1: radios, filtros UI, Excel/PDF, externos y checklist de migración |
| [`REPORTES_GENERALES_V1_AUDIT.md`](REPORTES_GENERALES_V1_AUDIT.md) | Estado actual de `/reportes-generales` (legacy): qué funciona, qué no, gaps pantalla/Excel |
| [`PLAN_REPORTES_GENERALES_V2.md`](PLAN_REPORTES_GENERALES_V2.md) | Plan para **Reportes generales v2**: arquitectura, carpetas, fases e implementación **sin tocar v1** |

## Regla de trabajo

- **v1** (`reportes-generales`, `cuentas.js`, PDF/Excel legacy): **congelado** salvo bugs críticos de producción.
- **v2** (`reportes-generales-v2`, archivos nuevos): todo el desarrollo hasta paridad y corte de menú.

SQL relacionado: [`../sql/`](../sql/).
