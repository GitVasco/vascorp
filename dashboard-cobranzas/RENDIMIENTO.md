# Rendimiento — Dashboard de Cobranzas

## Por qué Análisis (inicio-gerencia) va más rápido

El módulo **Análisis / inicio-gerencia** no recorre cada pago en `cuenta_ctejf` para los totales principales. Usa tablas **ya resumidas**, por ejemplo:

| Fuente | Uso |
|--------|-----|
| `totalesjf` | Ventas y cobranzas agregadas por día/mes/año |
| `ventajf` | Detalle de ventas (consultas acotadas) |
| Procedimientos `sp_1009_*`, `sp_1010_*` | Resúmenes precalculados |

El dashboard de cobranzas, en cambio, **agrupa en vivo** miles de filas de `cuenta_ctejf` con `SUM`, `GROUP BY` y `CASE` sobre `cod_pago`. Eso es más pesado aunque el resultado sea solo 6 cajas.

## Qué ya optimizamos en código

1. **Rangos de fecha** (`fecha >= '2025-05-01' AND fecha < '2025-06-01'`) en lugar de `YEAR()` / `MONTH()` → el índice en `fecha` sí se puede usar.
2. **Carga en dos fases**: KPIs en la página; mini gráficos por AJAX después.
3. **Menos consultas**: comparativo de 2 meses en 1 query; vendedores solo con cobranza en el año.
4. **1 query menos** en sparklines si ya filtraste por el top vendedor.

## Índices recomendados (importante)

Archivo listo para DBA: [indices-recomendados.sql](./indices-recomendados.sql)

```sql
CREATE INDEX idx_cctejf_tipmov_fecha
    ON cuenta_ctejf (tip_mov, fecha);
```

Opcional si el filtro por vendedor sigue lento:

```sql
CREATE INDEX idx_cctejf_tipmov_fecha_vendedor
    ON cuenta_ctejf (tip_mov, fecha, vendedor);
```

Comprobar antes de crear:

```sql
SHOW INDEX FROM cuenta_ctejf;
EXPLAIN SELECT SUM(monto) FROM cuenta_ctejf
WHERE tip_mov = '-' AND fecha >= '2025-05-01' AND fecha < '2025-06-01';
```

En `EXPLAIN`, conviene ver `type: range` o `ref` y `key: idx_cctejf_tipmov_fecha`, no `ALL` (escaneo completo).

## Mejora a medio plazo (como Análisis)

Si el dashboard crece (más gráficos, histórico largo), valorar:

- Tabla resumen **`cobranzas_resumen_dia`** (vendedor, fecha, monto, operaciones, devoluciones) alimentada por job nocturno o trigger.
- O reutilizar / ampliar **`totalesjf`** si ya tiene `total_pagos_soles` por día y alinea con la definición de cobranza del negocio.

Eso implica acuerdo con contabilidad/IT; el índice suele ser el mejor primer paso.
