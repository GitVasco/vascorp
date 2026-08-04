# Criterios técnicos — reporte ventas por zonas / geo

**Documento para jefatura (lectura):** [`VENTAS_ZONAS_ENE_JUN_2026.md`](VENTAS_ZONAS_ENE_JUN_2026.md)  
**SQL:** [`../../sql/reporte-ventas-zonas-geo-jefatura.sql`](../../sql/reporte-ventas-zonas-geo-jefatura.sql)  
**BD:** MariaDB 10.1 (sin CTE / `WITH`)

---

## Periodo del documento de jefatura

```sql
SET @fecha_ini = '2026-01-01';
SET @fecha_fin = '2026-07-01';  -- exclusivo → enero–junio 2026
```

---

## Criterios (versión jefatura: todas las ventas)

| Tema | Regla |
|------|--------|
| Monto | `ventajf.neto` (sin IGV) |
| Documentos | Venta real: `S02`, `S03`, `S70`, `E05`, `S05` (no pedidos `S01`) |
| Anulados | Excluidos |
| Vendedor | **Sin filtro** de `estado_decisiones` (incluye showroom, oficina, digitales, etc.) |
| Zona | Cascada cliente → grupo → ubigeo; solo zonas `estado = 1` |
| Sin zona | Se reporta aparte para cuadrar al total venta real |

> El módulo Mapas de zonas en pantalla sí filtra vendedores activos (`estado_decisiones = 1`). Este reporte de jefatura **no**.

### Cortes

| Vista | Agrupa por | Filtro |
|-------|------------|--------|
| Zonas Lima | Zona comercial | `macrozona = 'lima'` |
| Zonas Perú | Zona comercial | `macrozona IN ('peru_norte','peru_sur')` |
| Distritos Lima | Distrito | `(Dep=LIMA y Prov=LIMA) o Dep=CALLAO` |
| Departamentos | Departamento | Dep ≠ LIMA y ≠ CALLAO |
