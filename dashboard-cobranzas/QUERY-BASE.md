# Query base — Dashboard de Cobranzas

Query entregada como punto de partida. Los widgets del dashboard consumen agregaciones sobre este dataset (o vistas derivadas), no filas crudas en el front.

---

## SQL original

```sql
SELECT
    cc.tipo_doc,
    cc.num_cta,
    cc.cod_pago,

    CASE
        WHEN cc.cod_pago IN ('00', 'TR', '05', '06', '14', '16', '17', '18', '15', '80', '82')
        THEN 'EFECTIVO'

        WHEN cc.cod_pago IN ('13', '96')
        THEN 'DEVOLUCION'

        WHEN cc.cod_pago IN ('97', '10')
        THEN 'DESCUENTOS'

        ELSE 'OTROS'
    END AS ingreso,

    cc.doc_origen,
    cc.fecha,

    YEAR(cc.fecha) AS anno,
    MONTH(cc.fecha) AS mes,

    ELT(
        MONTH(cc.fecha),
        'Ene','Feb','Mar','Abr','May','Jun',
        'Jul','Ago','Sep','Oct','Nov','Dic'
    ) AS nom_mes,

    WEEK(cc.fecha, 1) AS semana_anual,
    CEIL(DAY(cc.fecha) / 7) AS semana_mes,

    CASE
        WHEN CEIL(DAY(cc.fecha) / 7) = 1 THEN '01 - 07'
        WHEN CEIL(DAY(cc.fecha) / 7) = 2 THEN '08 - 14'
        WHEN CEIL(DAY(cc.fecha) / 7) = 3 THEN '15 - 21'
        WHEN CEIL(DAY(cc.fecha) / 7) = 4 THEN '22 - 28'
        ELSE CONCAT('29 - ', DAY(LAST_DAY(cc.fecha)))
    END AS rango_semana_mes,

    DAY(cc.fecha) AS dia,
    DAYOFWEEK(cc.fecha) AS num_dia_semana,

    ELT(
        DAYOFWEEK(cc.fecha),
        'Dom','Lun','Mar','Mie','Jue','Vie','Sab'
    ) AS nom_dia,

    cc.monto,
    cc.cliente,
    c.nombre,
    cc.vendedor,
    cc.notas

FROM cuenta_ctejf cc

LEFT JOIN clientesjf c
    ON cc.cliente = c.codigo

WHERE
    YEAR(cc.fecha) IN ('2024','2025','2026')
    AND cc.tip_mov = '-';
```

---

## Alcance de los datos

| Criterio | Valor | Significado |
|----------|--------|-------------|
| Tabla principal | `cuenta_ctejf` | Movimientos de cuenta corriente |
| `tip_mov = '-'` | Solo cobranzas / abonos (salida de deuda del cliente) |
| Años | 2024, 2025, 2026 | Histórico para comparativos; el filtro de UI acotará el período activo |
| Join | `clientesjf` | Nombre del cliente para rankings y filtros |

---

## Campos y uso en el dashboard

### Identificación del movimiento

| Campo | Origen | Uso |
|-------|--------|-----|
| `tipo_doc` | `cc.tipo_doc` | Dona «Distribución por documento» (agrupar en Boleta/Factura/NC/Otros) |
| `num_cta` | `cc.num_cta` | Detalle / exportación |
| `cod_pago` | `cc.cod_pago` | Clasificación cruda; preferir `ingreso` en UI |
| `doc_origen` | `cc.doc_origen` | Trazabilidad |
| `notas` | `cc.notas` | Detalle opcional |

### Clasificación de ingreso (`ingreso`)

Derivado de `cod_pago`:

| `ingreso` | Códigos `cod_pago` |
|-----------|-------------------|
| EFECTIVO | 00, TR, 05, 06, 14, 16, 17, 18, 15, 80, 82 |
| DEVOLUCION | 13, 96 |
| DESCUENTOS | 97, 10 |
| OTROS | Resto |

Usado en: filtro «Tipo ingreso», dona tipo de ingreso, KPI devoluciones y descuentos.

### Tiempo

| Campo | Cálculo | Uso principal |
|-------|---------|---------------|
| `fecha` | `cc.fecha` | Filtros, orden, acumulados |
| `anno` | `YEAR(fecha)` | Comparativo anual, evolución 2024 vs 2025 |
| `mes` | `MONTH(fecha)` | Filtro mes, agrupación mensual |
| `nom_mes` | `ELT(mes, …)` | Etiquetas en gráficos |
| `dia` | `DAY(fecha)` | Cobranza por día, mejor día, días sin cobranza |
| `semana_anual` | `WEEK(fecha, 1)` | Análisis por semana ISO (si se necesita) |
| `semana_mes` | `CEIL(día/7)` | Semanas 1–5 dentro del mes |
| `rango_semana_mes` | CASE sobre semana_mes | Etiqueta «08 - 14», etc. |
| `num_dia_semana` | `DAYOFWEEK(fecha)` | 1=Dom … 7=Sab (MySQL) |
| `nom_dia` | `ELT(DAYOFWEEK, …)` | Heatmap (Lun–Dom) |

### Montos y actores

| Campo | Uso |
|-------|-----|
| `monto` | Todas las sumas, promedios y rankings |
| `cliente` | Filtro y clave de agrupación |
| `nombre` | Top clientes, etiquetas |
| `vendedor` | Filtro, mejor vendedor, top 10 |

---

## Filtros que aplicará la aplicación (sobre la query)

Parámetros típicos a añadir en `WHERE` (además de `tip_mov` y rango de años):

```sql
-- Ejemplo conceptual (valores desde request)
AND YEAR(cc.fecha) = :anno
AND MONTH(cc.fecha) = :mes          -- omitir para gráfico comparativo mensual
AND cc.vendedor = :vendedor         -- opcional
AND cc.cliente = :cliente           -- opcional
AND <ingreso CASE> = :tipo_ingreso  -- opcional; o repetir CASE en HAVING
```

---

## Mejoras previstas (no implementadas aún)

Lista para iterar la query sin cambiar el contrato del dashboard:

1. **Rendimiento:** índice compuesto sugerido `(tip_mov, fecha)` y opcional `(vendedor, fecha)`, `(cliente, fecha)`.
2. **WHERE años:** usar enteros `IN (2024, 2025, 2026)` en lugar de strings.
3. **Vista o CTE:** materializar el `CASE ingreso` una vez (`WITH base AS (…)`) y agregar en consultas hijas por widget.
4. **Agregación en SQL:** evitar traer todas las filas al PHP; un endpoint por familia de widgets (KPIs, series diarias, rankings).
5. **Signo de montos:** confirmar si devoluciones/descuentos ya vienen negativos en `monto`; alinear KPI «Dev. y descuentos» con negocio.
6. **`tipo_doc`:** tabla de mapeo Boleta/Factura/Nota crédito/Otros si los códigos no son literales.
7. **Meta mensual** (gráfico evolución): no está en esta query; vendrá de configuración o tabla aparte.
8. **Nombre vendedor:** si solo hay código en `vendedor`, valorar join a tabla de vendedores (como con clientes).

---

## Archivo SQL reutilizable

Copia lista para pruebas en cliente SQL: [query-base.sql](./query-base.sql)
