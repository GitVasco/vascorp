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

### Bloque 0 — Base técnica

1. Ruta, permiso, menú, shell vacío con filtros.
2. Endpoint AJAX con parámetros validados (`anio`, `mes`, `vendedor`, `periodo_a`, `periodo_b`).
3. Definir fuente de ventas y cobranzas (mismas reglas que reportes/dashboards actuales).
4. Criterio: sin SQL concatenado; anulados/cancelados fuera.

### Bloque 1 — KPIs superiores

Cajas:

- Venta mes / YTD + Δ% vs año ant.
- Cobranza mes / YTD + Δ% vs año ant.
- % recuperación del período (definición en Bloque 5)
- Proyección vs real del mes (cuando exista Bloque 7)

### Bloque 2 — Ventas mes a mes (#1)

- Gráfico barras/línea: venta por mes del año seleccionado
- Tabla resumen: mes | venta | % del total año

### Bloque 3 — Ventas vs año pasado (#2)

- Misma serie con N y N-1 superpuestas
- Tabla: mes | venta N | venta N-1 | Δ abs | Δ %

### Bloque 4 — Ventas períodos específicos (#3)

- Selectores período A y período B (fecha desde–hasta o mes/año)
- Gráfico comparativo + tabla totales y, si aplica, por mes dentro del rango

### Bloque 5 — Cobranzas mes a mes / vs N-1 / períodos (#4)

- Replicar Bloques 2–4 con monto cobrado (misma UX)
- Alinear definición de “cobranza” con dashboard CxC / rendiciones vigentes

### Bloque 6 — Origen de la cobranza / tasa de recuperación (#5)

Pregunta tipo: “En julio cobramos 2M; ¿cuánto era de junio, mayo, años anteriores?”

- Filtro: período de cobro (ej. julio 2026)
- Tabla/gráfico: monto cobrado en ese período **agrupado por mes/año de origen del documento** (fecha factura / emisión)
- % de cada origen sobre el total cobrado del período
- KPI: tasa de recuperación (definir y documentar fórmula al implementar; ej. cobrado del período ÷ cartera elegible del mismo origen, o desglose de antigüedad del cobro — confirmar con negocio en este bloque)

### Bloque 7 — Origen global y por vendedor (#6)

- Mismo Bloque 6 con filtro vendedor = Todos | uno
- Opcional: tabla pivote vendedor × mes origen

### Bloque 8 — Proyección de cobranzas (#7)

- Esperado a cobrar (por vencer / vencido / calendario) vs real del mes
- Gráfico proyección vs real; tabla por vendedor o por tramo
- Reusar lógica de `dashboard-cxc` (proyección pagos) si aplica; no duplicar reglas contradictorias

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
