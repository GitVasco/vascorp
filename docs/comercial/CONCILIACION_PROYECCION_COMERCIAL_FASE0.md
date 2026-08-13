# Conciliación Fase 0 — Proyección comercial vs ficha gerencial

Documento de apoyo para validar que el módulo de proyección reutiliza la misma regla de ventas que Análisis de modelos.

## Regla de venta válida (única)

Fuente: `ModeloFichaGerencialModelos` (métodos públicos `tiposVenta`, `sqlTiposVenta`, `sqlCabeceraValida`).

- Tipos incluidos: `S02`, `S03`, `S70`, `S05`, `E05`
- Cabecera: existe en `ventajf` con el mismo `tipo` + `documento` + `fecha` y estado distinto de `ANULADO`
- Unidades / venta neta: `SUM(cantidad)` / `SUM(total)` sobre `movimientosjf_AAAA` unidos a `articulojf` por artículo → modelo

La proyección **no** redefine esta regla: llama a esos helpers y a `mdlResolverTablaMovimientos` / `mdlSqlFuenteMovimientos`.

## Qué comparar

Desde la pantalla `proyeccion-comercial-modelos` → bloque “Conciliar vs ficha gerencial”, o endpoint AJAX `accion=conciliar`.

Para un modelo + año + mes (ya cerrado o pasado):

| Campo | Ficha | Proyección Fase 0 |
|---|---|---|
| Unidades netas del mes | `mdlVentasMensuales` | `mdlVentasMensualesLote` |
| Venta neta del mes | idem | idem |
| Precio lista 9 | `mdlPrecio9Valorizado` (último `preciojf` del modelo) | `mdlPreciosLista9` (MAX id por modelo) |
| Inventario | `mdlInventarioResumen` (SKU activos del modelo) | `mdlInventarioPorModelos` (misma agregación) |

Criterio OK: diferencia de unidades &lt; 0.0001 y de venta neta &lt; 0.01.

## Casos a probar manualmente

1. Modelo con venta habitual en un mes cerrado (ej. mes anterior).
2. Modelo sin ventas en ese mes → ambos en 0.
3. Año sin tabla `movimientosjf_AAAA` → el historial omite ese año; la conciliación de un mes de ese año falla con mensaje claro.
4. Modelo sin lista 9 → `sin_lista9` / precio null en ambos lados.
5. Confirmar que **ningún** flujo de este módulo escribe en `articulojf.proyeccion`.

## Fuera de alcance de esta conciliación

- Meses futuros del plan (la ficha no admite futuro; la proyección sí para planificar).
- Sugerencia ponderada (es cálculo propio; no existe en ficha).
- Publicación / borradores (Fase 1).

## SQL pendiente de ejecutar

```text
docs/sql/proyeccion-comercial-modelos.sql
```

Hasta no ejecutarlo, Fase 0 de lectura funciona; Fase 1 (persistencia) no.
