# Plan: Dashboard Gerencial (ventas + cobranzas)

## Objetivo

Vista gerencial orientada a reuniones: gráficos y tablas para responder rápido
sobre ventas, cobranzas, comparativos, origen de recuperación y proyección.

- Ruta: `index.php?ruta=dashboard-gerencial`
- Permiso: `gestion_comercial` → `dashboard_gerencial` → `ver` (por ahora solo ID `6`)
- Stack: PHP, AdminLTE/Bootstrap 3, jQuery, Chart.js, DataTables (igual que el resto)
- No reemplaza `dashboard-cxc` ni `inicio-gerencia`
- `dashboard-cobranzas` queda oculto del menú (código legado; no usarlo como UI)

## Alcance confirmado (reunion gerencia / supervisión)

| # | Pregunta de negocio | Bloque UI |
|---|---------------------|-----------|
| 1 | ¿Cuánto vendimos mes a mes? | Ventas mes a mes |
| 2 | Comparar con el año pasado | Ventas vs N-1 |
| 3 | Comparar períodos específicos | Ventas período A vs B |
| 4 | Lo mismo para cobranzas | Cobranzas (1–3) |
| 5 | De lo cobrado en un período, ¿de qué mes fueron las facturas? (tasa / origen de recuperación) | Origen cobranza |
| 6 | Punto 5 global y por vendedor | Origen + filtro vendedor |
| 7 | Proyección de cobranzas | Proyección |

## Archivos

### Crear

- `vistas/modulos/dashboard-gerencial.php` — contenedor, filtros, includes
- `vistas/modulos/dashboard-gerencial/` — un PHP por bloque
- `vistas/js/dashboard-gerencial.js`
- `vistas/css/dashboard-gerencial.css`
- `controladores/dashboard-gerencial.controlador.php`
- `modelos/dashboard-gerencial.modelo.php`
- `ajax/dashboard-gerencial.ajax.php`

### Ajustar

- `controladores/permisos-modulos.json` — módulo `dashboard_gerencial` (`ver`: `[6]`)
- `vistas/plantilla.php` — título, CSS, JS, ruta
- `vistas/modulos/menu.php` — entrada bajo Crédito y cobranzas; sin entrada a `dashboard-cobranzas`
- `index.php` — require controlador/modelo cuando existan

## Filtros globales (barra superior)

- Año / mes de referencia (o rango)
- Comparar con: año anterior | período custom (desde–hasta A vs desde–hasta B)
- Vendedor: Todos | código
- Botón aplicar (URL o AJAX único)

## Orden de implementación (punto a punto)

No pasar al siguiente bloque sin validar totales con datos reales.

### Bloque 0 — Base técnica ✅

1. Ruta, permiso, menú, shell con filtros.
2. Endpoint AJAX `ajax/dashboard-gerencial.ajax.php` (`accion=base|kpis`) con parámetros validados:
   `anio`, `mes`, `vendedor`, `modo` (`vs_anio_ant`|`periodos`), `periodo_a_*`, `periodo_b_*`.
3. Fuentes de datos (contrato):
   - **Venta global:** `ControladorMovimientos::ctrTotalesSolesGerencia` → `vtas_soles`
   - **Venta por vendedor:** `ModeloMetasVendedor::mdlAvanceVentasDashboard` → `venta_real`
   - **Cobranza global:** mismos totales → `pagos_soles`
   - **Cobranza por vendedor:** `ControladorDashboardCobranzas::ctrKpisSuperiores` (efectivo en `cuenta_ctejf`)
   - **Filtro vendedores:** lista fija `dashboard-cobranzas/vendedores-filtro.php`
4. Criterio: parámetros validados en `ctrParseFiltros`; sin SQL concatenado con input de usuario.
5. KPIs cabecera con datos reales (recuperación y proyección quedan en `—` hasta bloques 6–8).

### Bloque 1 — KPIs superiores

Cajas:

- Venta mes / YTD + Δ% vs año ant.
- Cobranza mes / YTD + Δ% vs año ant.
- % recuperación del período (definición en Bloque 5)
- Proyección vs real del mes (cuando exista Bloque 7)

### Bloque 2 — Ventas mes a mes (#1) ✅

- Gráfico barras: venta por mes del año seleccionado (`ctrVentasMensual`)
- Tabla: mes | venta | % del total año
- Fuentes: global `totalesjf`; por vendedor `ventajf` (tipos venta real)
- Endpoint: incluido en `accion=base` y `accion=ventas_mensual`
- Año en curso: meses hasta el mes actual; años cerrados: 12 meses

### Bloque 3 — Ventas vs año pasado (#2) ✅

- Barras agrupadas N vs N-1 (`ctrVentasVsAnioPasado`)
- Tabla: mes | venta N | venta N-1 | Δ abs | Δ %
- Mismos meses alineados; año en curso hasta mes actual
- Endpoint: `accion=base` y `accion=ventas_vs_anio`

### Bloque 4 — Ventas períodos específicos (#3) ✅

- Filtros: modo «Períodos A / B» + fechas desde–hasta (ya en barra)
- Totales A vs B + Δ / Δ%
- Gráfico totales + barras por mes alineadas por número de mes (Ene A vs Ene B)
- Tabla mensual detalle
- Fuente: `ventajf` (tipos venta real); respeta vendedor
- Endpoint: `accion=base` y `accion=ventas_periodos`

### Bloque 5 — Cobranzas mes a mes / vs N-1 / períodos (#4) ✅

- Misma UX que ventas (mensual, vs N-1, períodos A/B)
- **Sin IGV 18%** (`monto / 1.18`) en cobranzas y proyección de este dashboard, para comparar con ventas netas
- Fuentes:
  - Global mensual/vs año: `totalesjf.total_pagos_soles`
  - Por vendedor / períodos: `cuenta_ctejf` tip_mov=`-` + códigos EFECTIVO (`mrSqlInCodigosCobranzaEfectiva`)
- Endpoints: `cobranzas_mensual`, `cobranzas_vs_anio`, `cobranzas_periodos` (+ `base`)

### Bloque 6 — Origen de la cobranza / tasa de recuperación (#5) ✅

Pregunta tipo: “En julio cobramos 2M; ¿cuánto era de junio, mayo, años anteriores?”

- Período de cobro: año/mes del filtro (o Período A si modo períodos)
- Origen documento: `fecha_ori` del abono; fallback `MIN(fecha)` del cargo `tip_mov='+'`
- Solo cobranza EFECTIVO (`mrSqlInCodigosCobranzaEfectiva`)
- Gráfico horizontal + tabla (mismas filas, mismo orden, altura dinámica): mes origen | monto | %
- **KPI % cobro mismo mes:**  
  `(cobro con origen en el mismo mes calendario del pago) / (total cobrado del período) × 100`
- **Ventas del período vs recuperado hasta hoy:**  
  ventas del rango / cobranza de docs con origen en ese rango (pagos hasta hoy) → `% recuperado`
- **Mes a mes del año:** ventas, recuperado hasta hoy, % recup., cobro mismo mes, % mismo mes  
  (`% mismo mes = cobro mismo mes / ventas del mes`)
- **Aging del cobro del período:** buckets 0–30 / 31–60 / 61–90 / 91–180 / +180 / sin origen  
  (días entre fecha origen y fecha de pago)
- Endpoint: `accion=origen_cobranza` (+ `base`)

### Bloque 7 — Origen global y por vendedor (#6) ✅

- Respeta filtro vendedor (Todos | uno)
- Con vendedor = Todos: tabla top 15 vendedores (cobranza, mismo mes, % cobro mismo mes)

### Bloque 8 — Proyección de cobranzas (#7) ✅

- Reutiliza `ControladorDashboardCxc::ctrProyeccionPagos` (mismas reglas de cartera)
- Mes de corte = mes del filtro (si año completo → mes actual / dic)
- Por cada mes del horizonte: proyección (saldo `fecha_ven`) vs real (cobranza efectiva)
- KPI % cumplimiento = `real_mes / proyeccion_mes × 100`
- Muestra también vencido pendiente, incobrables, horizonte y cartera total
- Endpoint: `accion=proyeccion_cobranzas` (+ `base`)

## Criterios de aceptación globales

- Respuestas en &lt; unos segundos con filtros típicos (índices si hace falta)
- Comparativos N vs N-1 y A vs B coherentes entre gráfico y tabla
- Origen de cobranza cuadra con el total cobrado del período
- Solo usuarios con `dashboard_gerencial.ver` ven el menú y la ruta
- `dashboard-cobranzas` no aparece en el menú

## Fuera de alcance (por ahora)

- Export Excel/PDF
- Drill-down a documento individual (salvo que un bloque lo pida después)
- Metas / retos / comisiones
- Cambios a `dashboard-cxc`
