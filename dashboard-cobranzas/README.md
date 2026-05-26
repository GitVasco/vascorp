# Dashboard de Cobranzas

Documentación de referencia para el desarrollo del dashboard solicitado.

| Archivo | Contenido |
|---------|-----------|
| [CAJAS.md](./CAJAS.md) | Descripción resumida de cada widget/caja del mockup |
| [QUERY-BASE.md](./QUERY-BASE.md) | Query base, campos derivados y notas para optimización |
| [RENDIMIENTO.md](./RENDIMIENTO.md) | Por qué es más lento que Análisis e índices recomendados |
| [indices-recomendados.sql](./indices-recomendados.sql) | Script SQL para crear índices en `cuenta_ctejf` |
**Mockup:** `dashboard cobranza.png` (raíz del proyecto)

**Fuente de datos:** `cuenta_ctejf` + `clientesjf`, movimientos de cobranza (`tip_mov = '-'`).

**Orden sugerido de implementación:** ver sección «Prioridad» al final de [CAJAS.md](./CAJAS.md).
