# Plan — Reportes generales v2

Objetivo: reconstruir **Reportes generales** en una vía nueva, **sin modificar v1** hasta que v2 esté validado y se haga el corte de menú.

Pantalla legacy (no tocar): `/reportes-generales`  
Pantalla nueva: **`/reportes-generales-v2`**

Auditoría v1: [`REPORTES_GENERALES_V1_AUDIT.md`](REPORTES_GENERALES_V1_AUDIT.md)

---

## Principios

| # | Regla |
|---|--------|
| 1 | **No editar** `reportes-generales.php`, el handler `.btnGenerarReporteCuenta` en `cuentas.js`, ni los PDF/Excel legacy salvo bug crítico en producción. |
| 2 | Todo código nuevo bajo prefijos/rutas **v2** (ver estructura abajo). |
| 3 | **Un catálogo** declara cada reporte: id, etiqueta, filtros, salidas, estado. |
| 4 | **Misma consulta** para preview, Excel y PDF; solo cambia el render. |
| 5 | PDO en consultas nuevas; no `mysql_*`. |
| 6 | Si un reporte no está implementado en v2, **Vista previa muestra error** (SweetAlert), no queda mudo. |
| 7 | Reutilizar lógica probada del modelo v1 **solo vía capa de servicio v2** (adaptadores), no copiar SQL a ciegas. |
| 8 | UX tipo **Aida** (`/proyectos/aida/admin`): catálogo + **Vista previa** + Excel/PDF; no radio pantalla/Excel v1. |

---

## UX objetivo (preview + descarga) — referencia Aida

**Proyecto de referencia:** `/Users/joel/Proyectos/aida/admin` → ruta `/reports`

| Pieza | Ruta en Aida |
|-------|----------------|
| Vista | `views/pages/reports/reports.php` |
| Catálogo JS | `public/customs/js/reports/reports.config.js` |
| Lógica UI | `public/customs/js/reports/reports.js` |
| API preview | `ajax/reports/<tpl>.php` (ej. `cxc-overdue.php`) |
| Export Excel/PDF | `ajax/reports/export.php` + `ReportsExportService` |
| Catálogo servidor | `src/Helpers/ReportsCatalog.php` |

### Flujo Aida (copiar en v2)

1. **Catálogo lateral** — lista de plantillas por grupo (Ventas / Cobranzas).
2. **Panel derecho** — título, hint, filtros dinámicos según plantilla.
3. **Vista previa** — botón explícito; AJAX devuelve `{ rows, totalRows, truncated, columnTotals }` → tabla + KPIs.
4. **Excel / PDF** — mismos filtros; `export.php?format=xlsx|pdf&tpl=...`; deshabilitados hasta que hay preview válida (en Aida exige `tpl.live`).

En vascorp v2 **no** usar radio “pantalla vs Excel” (v1). Siempre: **Vista previa → Excel / PDF**.

### Layout v2 (equivalente Aida)

```
┌──────────────────┬──────────────────────────────────────────────────┐
│ CATÁLOGO         │ [Título] [badge grupo]                           │
│ (grupos + buscar)│ [ Vista previa ] [ Excel ] [ PDF ]               │
│                  │ Filtros (row dinámico)                           │
│ • Doc por cobrar │ KPIs: N registros · Total S/ …                   │
│ • Vencidos       │ ┌────────────────────────────────────────────┐   │
│ • Pagos          │ │ thead / tbody / tfoot (misma data export)  │   │
│ • …              │ └────────────────────────────────────────────┘   │
└──────────────────┴──────────────────────────────────────────────────┘
```

- **Vista previa** → `ajax/reportes-generales-v2.ajax.php?accion=preview&reporte=...`
- **Excel / PDF** → mismo endpoint u `export.php` v2 con `formato=excel|pdf`
- Si `truncated: true` → badge “Vista previa N de total” (como Aida)

---

## Equivalencias Aida (CxC) ↔ Vascorp v2

Reportes de cobranzas ya diseñados en Aida que deben replicar el **mismo patrón** en vascorp v2:

| Plantilla Aida | id Aida | Equivalente vascorp v2 | Notas |
|----------------|---------|------------------------|-------|
| Cartera actual | `cxc-portfolio` | `doc_por_cobrar` | Pendientes / saldo vivo |
| Vencidos | `cxc-overdue` | `doc_vencidos` | fecha_ven &lt; hoy |
| Por vencer | `cxc-upcoming` | `doc_no_vencidos` | Próximos a vencer (ajustar criterio v1) |
| Cobros del periodo | `cxc-collections` | `pagos` | Movimientos `-` en rango |
| Estado de cuenta | `cxc-statement` | `estado_cuenta` | Cliente obligatorio en Aida |
| Letras | `cxc-letters` | `letras_en_cartera` + parte de `letras_por_aceptar` | Aida unifica letras abiertas |
| Cartera por vendedor | `cxc-sellers` | Agrupación en `doc_por_cobrar` (orden vendedor) | Ranking, no listado doc |
| Antigüedad cartera | `cxc-aging` | **Nuevo en v2** o Fase 3 | No existe en v1 legacy |
| Línea de crédito | `cxc-credit-lines` | Fuera de reportes generales | Ya tiene módulo `/linea-credito` |

Reportes **solo en vascorp v1** (sin plantilla Aida; igual van con preview + Excel + PDF):

| id v2 | Motivo preview |
|-------|----------------|
| `doc_protestados` | Listado tabular |
| `letras_por_imprimir` | Listado antes de imprimir letra |
| `letras_por_aceptar` | Tabla o informe por vendedor |
| `doc_cancelados` | Listado |
| `saldos_fecha` | Detalle a fecha corte |
| `doc_por_banco_estado` | Informe agrupado |
| `doc_por_estado_banco` | Informe agrupado |
| `movimientos_ctacte` | Tabla pesada paginada |
| `resumen_saldos_fecha` | Resumen por cliente |

**Todos** los de la tabla anterior + equivalencias Aida → **Vista previa + Excel + PDF**, salvo `pagos_comisiones`.

---

## Modo de presentación por reporte

| Modo | Descripción | Reportes |
|------|-------------|----------|
| **`preview_tabla`** | Tabla en pantalla + Excel + PDF. Patrón estándar v2. | Ver lista abajo |
| **`preview_informe`** | Preview HTML con bloques/subtotales (como informe semanal); Excel + PDF además. | Agrupaciones banco/estado, resumen saldos, letras por aceptar (si se mantiene formato carta) |
| **`preview_tabla_pesada`** | Tabla paginada + export completo; aviso si supera límite preview. | Movimientos ctacte, doc. por cobrar sin filtro |
| **`fuera_alcance`** | No implementar hasta definición. | Pagos-comisiones |

### Reportes con **`preview_tabla`** (todos: Consultar → tabla → Excel + PDF)

| id v2 | Motivo |
|-------|--------|
| `doc_por_cobrar` | Listado tabular; hoy PDF abre directo sin ver datos |
| `doc_vencidos` | Igual |
| `doc_no_vencidos` | Igual |
| `doc_protestados` | Igual |
| `pagos` | Listado de movimientos `-`; filtros cliente/fechas |
| `estado_cuenta` | Debe alinearse con Excel v1 (hoy pantalla legacy es ticket incompleto) |
| `saldos_fecha` | Detalle por documento a fecha corte |
| `letras_en_cartera` | Listado filtrable |
| `letras_por_imprimir` | Listado antes de ir a impresión unitaria de letra |
| `doc_cancelados` | Listado tabular |

### Reportes con **`preview_informe`** (tabla + subtotales visibles en preview)

| id v2 | Motivo |
|-------|--------|
| `doc_por_banco_estado` | Agrupación banco → estado; subtotales por grupo |
| `doc_por_estado_banco` | Agrupación estado → banco |
| `resumen_saldos_fecha` | Resumen por cliente/moneda, no solo detalle |
| `letras_por_aceptar` | Opcional: preview tabla **o** vista informe por vendedor (como `letras_aceptar.php`); mínimo tabla en v2 Fase 2 |

### Reportes con **`preview_tabla_pesada`**

| id v2 | Notas |
|-------|--------|
| `movimientos_ctacte` | Muchas filas; preview paginada (ej. 100/página), export sin límite |

### No preview estándar

| id v2 | Notas |
|-------|--------|
| `pagos_comisiones` | Fuera de alcance |

---

## Catálogo v2 (IDs legibles)

Reemplazar `option5`, `option8`, etc. por ids estables.

Columnas **Modo UX** y **Salidas** (preview siempre; export tras consultar):

| id v2 | Etiqueta | Origen v1 | Fase | Modo UX | Excel | PDF |
|-------|----------|-----------|------|---------|-------|-----|
| `doc_por_cobrar` | Doc. por cobrar | `pendiente` | 1 | preview_tabla | Sí | Sí |
| `doc_vencidos` | Doc. vencidos | `pendienteVencidoMenor` | 1 | preview_tabla | Sí | Sí |
| `doc_no_vencidos` | Doc. no vencidos | `pendienteVencidoMayor` | 1 | preview_tabla | Sí | Sí |
| `doc_protestados` | Doc. protestados | `protestado` | 1 | preview_tabla | Sí | Sí |
| `pagos` | Pagos | `pagos` | 1 | preview_tabla | Sí | Sí |
| `estado_cuenta` | Estado de cuenta | `fechaActualSaldo` | 1 | preview_tabla | Sí | Sí |
| `saldos_fecha` | Saldos a una fecha | `fechaSaldo` | 1 | preview_tabla | Sí | Sí |
| `letras_por_aceptar` | Letras por aceptar | `estadoEnvioVacio` | 2 | preview_informe | Sí | Sí |
| `letras_en_cartera` | Letras en cartera | `unicoCartera` | 2 | preview_tabla | Sí | Sí |
| `letras_por_imprimir` | Letras por imprimir | `option5` | 2 | preview_tabla | Sí | Sí |
| `doc_cancelados` | Doc. cancelados | `cancelado` | 2 | preview_tabla | Sí | Sí |
| `doc_por_banco_estado` | Doc. por banco/estado | `option8` | 3 | preview_informe | Sí | Sí |
| `doc_por_estado_banco` | Doc. por estado/banco | `option9` | 3 | preview_informe | Sí | Sí |
| `movimientos_ctacte` | Movimientos en Ctas.ctes. | `option11` | 3 | preview_tabla_pesada | Sí | Sí |
| `resumen_saldos_fecha` | Rsm saldos a una fecha (S/) | `option15` | 3 | preview_informe | Sí | Sí |
| `pagos_comisiones` | Pagos-comisiones | `option16` | — | fuera_alcance | — | — |

---

## Estructura de carpetas propuesta

```
vistas/modulos/cuentas-corrientes/
  reportes-generales-v2.php

vistas/js/
  reportes-generales-v2.js

controladores/
  reportes-generales-v2.controlador.php

modelos/
  reportes-generales-v2.modelo.php

config/
  reportes-generales-v2.catalogo.php

vistas/reportes_v2/
  pdf/
  excel/

ajax/
  reportes-generales-v2.ajax.php
```

Flujo AJAX preview:

```json
POST reportes-generales-v2.ajax.php
{ "accion": "preview", "reporte": "pagos", "inicio": "...", "fin": "...", "cli": "" }

→ { "ok": true, "filas": [...], "totales": {...}, "total_registros": 120, "preview_limitado": false }
```

**Ruta HTTP:** `reportes-generales-v2` → menú beta; legacy intacto hasta Fase 4.

### Filtros comunes (mostrar/ocultar por reporte en catálogo)

| Filtro | Clave | Reportes típicos |
|--------|-------|------------------|
| Orden primario | `orden1` | Cobranza, pagos |
| Orden secundario | `orden2` | Cobranza, pagos |
| Tipo documento | `tip_doc` | Cobranza, saldos |
| Tipo cancelación | `canc` | Pagos |
| Cliente | `cli` | Cobranza, pagos, EC |
| Vendedor | `vend` | Cobranza, pagos, letras aceptar, EC |
| Banco | `banco` | Cobranza, letras |
| Fecha inicio / fin | `inicio`, `fin` | Pagos, EC, saldos, movimientos |

El formulario v2 **solo muestra** los filtros que el catálogo marca para el reporte seleccionado.

---

## Arquitectura de ejecución

```
[UI v2] → [JS: Consultar → AJAX preview]
       → [controlador v2: DTO filtros → filas JSON]
       → [tabla en pantalla + totales]
       → [Excel / PDF: misma consulta, otro render]
```

---

## Reutilización de v1 (sin tocar archivos v1)

| Necesidad v2 | Estrategia |
|--------------|------------|
| SQL cobranza / pagos ya probado | Wrapper en `reportes-generales-v2.modelo.php` → `ModeloCuentas::mdlMostrarReporte*` |
| Estado cuenta | Portar lógica de `rpt_estado_cuenta.php` a servicio PDO v2 |
| Letras por aceptar | `mdlLetrasAceptar` + preview informe |
| Letras cartera | Criterio CARTERA de gerencia |
| Saldos fecha | `mdlSaldoFecha` |

**No** enlazar v2 a URLs legacy en producción final.

---

## UI v2 (mejoras sobre v1)

- Título: **Reportes generales** (quitar “Administrar agencias”).
- Agrupar radios: **Cobranza** | **Letras** | **Movimientos y saldos** | **Pagos**.
- Ocultar “Por Fch. Pago” salvo reporte `pagos`.
- Ocultar banco salvo reportes que lo usen.
- Botón **Vista previa** (como Aida); tras éxito habilitar **Excel** y **PDF**.

---

## Fases de implementación

### Fase 0 — Esqueleto (sin reportes de negocio) ✅

- [x] Ruta `reportes-generales-v2` + ítem menú beta
- [x] `reportes-generales-v2.php` + `reportes-generales-v2.js`
- [x] `reportes-generales-v2.config.php` con estados `pendiente | listo | fuera_alcance`
- [x] Botón **Vista previa** → AJAX; SweetAlert si reporte pendiente; Excel/PDF deshabilitados
- [x] Layout filtros dinámico según catálogo (16 plantillas, 4 grupos)

### Fase 1 — Paridad mínima usable (7 reportes)

Prioridad negocio según auditoría v1:

1. `doc_por_cobrar` — preview + Excel + PDF con todos los filtros
2. `doc_vencidos`
3. `doc_no_vencidos`
4. `doc_protestados`
5. `pagos` — paridad filtros PDF/Excel (cliente, cancelación, vendedor, órdenes)
6. `estado_cuenta` — misma definición pantalla/Excel; pantalla ya no ticket “solo inicio”
7. `saldos_fecha` — respetar filtros acordados o ocultarlos en UI

**DoD Fase 1:** checklist manual por reporte (ver abajo) en verde.

### Fase 2 — Letras y cancelados

8. `letras_por_aceptar` (select vendedor + mes; sin prompt)
9. `letras_en_cartera`
10. `letras_por_imprimir` (definir criterio con negocio: sin nro. único, estado 01, etc.)
11. `doc_cancelados`

### Fase 3 — Agrupaciones y movimientos

12. `doc_por_banco_estado`
13. `doc_por_estado_banco`
14. `movimientos_ctacte`
15. `resumen_saldos_fecha`

### Fase 4 — Corte

- [ ] Usuarios piloto solo en v2 (1–2 semanas)
- [ ] Cambiar menú: “Reportes generales” → v2; legacy oculto o renombrado “(legacy)”
- [ ] Documentar rollback: volver a mostrar ítem v1

---

## Checklist de prueba (plantilla por reporte)

Para cada id v2 cuando pase a `listo`:

```
Reporte: _______________
[ ] Vista previa muestra tabla + totales (KPIs)
[ ] Excel descarga mismas filas que preview
[ ] PDF descarga mismas filas que preview
[ ] Filtros según catálogo aplicados
[ ] Preview vacía sin error PHP
[ ] v1 legacy sin cambios
```

---

## Registro de avance (actualizar al implementar)

| id v2 | Estado | Preview | Excel | PDF | Notas |
|-------|--------|---------|-------|-----|-------|
| `doc_por_cobrar` | pendiente | — | — | — | |
| `doc_vencidos` | pendiente | — | — | — | |
| `doc_no_vencidos` | pendiente | — | — | — | |
| `doc_protestados` | pendiente | — | — | — | |
| `pagos` | pendiente | — | — | — | |
| `estado_cuenta` | pendiente | — | — | — | |
| `saldos_fecha` | pendiente | — | — | — | |
| `letras_por_aceptar` | pendiente | — | — | — | preview_informe |
| `letras_en_cartera` | pendiente | — | — | — | |
| `letras_por_imprimir` | pendiente | — | — | — | |
| `doc_cancelados` | pendiente | — | — | — | |
| `doc_por_banco_estado` | pendiente | — | — | — | |
| `doc_por_estado_banco` | pendiente | — | — | — | |
| `movimientos_ctacte` | pendiente | — | — | — | preview_tabla_pesada |
| `resumen_saldos_fecha` | pendiente | — | — | — | |
| `pagos_comisiones` | fuera_alcance | — | — | — | |

---

## Próximo paso de desarrollo

**Pausado 2026-09-01** — ver [`ESTADO_REPORTES_GENERALES_V2.md`](ESTADO_REPORTES_GENERALES_V2.md).

Fase 0 ✅ cerrada. Al retomar: **Fase 1** empezando por `doc_por_cobrar` y `pagos` (servicio v2 + preview + Excel + PDF).

**No tocar v1** hasta completar Fase 1 y validación con usuario.
