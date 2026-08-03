# Reestructura Lima — 3 zonas + zonas especiales

**Estado:** en progreso  
**Última actualización:** 2026-08-03  

---

## Modelo acordado

| Tipo | Zonas | Asignación |
|------|-------|------------|
| **Territorial** | Zona 1, 2, 3 | ubigeo → zona (automático) |
| **Territorial propia** | La Victoria, Lima Cercado | ubigeo → su zona (**no** entran a Z1/Z2/Z3) |
| **Solo manual** | Gamarra, Distribuidores | override cliente/grupo; **sin** ubigeo |

**La Victoria / Cercado:** varios equipos atienden clientes ahí → reporte por **vendedor** dentro de la zona (sección 5).

**Zonas viejas desactivadas:** `LIM_CENTRO`, `LIM_NORTE`, `LIM_ESTE`, `LIM_SUR`, `LIM_MODERNA`, `CALLAO`.  
**Siguen activas:** Z1/Z2/Z3, Victoria, Cercado, Gamarra, Distribuidores, **NORTE_CHICO** (pendiente definición).

Los **14 distritos Lima** que aún colgaban de zonas viejas quedan **sin zona activa por ubigeo** hasta que Supervisión indique destino.

---

## Checklist maestro

### Fase 0 — Decisiones

- [x] Zona 1, 2, 3 · Callao → Z1
- [x] La Victoria → `LIM_VICTORIA` (ubigeo propio)
- [x] Lima Cercado → `LIM_CERCADO` (ubigeo propio)
- [x] Distribuidores + Gamarra → solo manual
- [x] **Desactivar zonas viejas** → `zonas-comerciales-desactivar-zonas-viejas.sql`

### Fase 2 — BD

- [x] Distribuidores, Victoria, Cercado, Z1/Z2/Z3 creadas
- [x] Ejecutar `zonas-comerciales-victoria-cercado-ubigeo.sql`

### Fase 3 — Migración

- [x] Z1=13, Z2=9, Z3=12 (`migrar-zona-1-2-3.sql`)
- [x] Victoria + Cercado ubigeos (`victoria-cercado-ubigeo.sql`)
- [ ] 14 distritos pendientes (Supervisión)
- [ ] Verificar mapa
- [x] Bandeja **Distritos pendientes** en Zonas comerciales (libres / zona inactiva)

### Fase 4 — Vendedores y clientes

- [ ] Vendedores por zona
- [ ] Gamarra → override manual `LIM_ECONOMICA` donde aplique
- [ ] Distribuidores → override manual `LIM_DISTRIBUIDORES`
- [ ] Grupos empresariales

### Fase 5 — Cierre

- [x] Desactivar zonas viejas (`desactivar-zonas-viejas.sql`)
- [ ] Norte Chico — pendiente Supervisión
- [ ] Comunicar nomenclatura Z1/Z2/Z3 al equipo

---

## Catálogo

| Código | Nombre | Ubigeo |
|--------|--------|--------|
| `LIM_ZONA_1` | Lima — Zona 1 | ✅ 13 |
| `LIM_ZONA_2` | Lima — Zona 2 | ✅ 9 |
| `LIM_ZONA_3` | Lima — Zona 3 | ✅ 12 |
| `LIM_VICTORIA` | La Victoria | 1 distrito |
| `LIM_CERCADO` | Lima Cercado | 1 distrito |
| `LIM_ECONOMICA` | Gamarra | manual |
| `LIM_DISTRIBUIDORES` | Distribuidores | manual |

---

## Distritos por zona

### Zona 1
Puente Piedra · Comas · Carabayllo · Los Olivos · Magdalena · Jesús María · SJL · **Callao completo**

### Zona 2
Independencia · SMP · Breña · Lince · Pueblo Libre · San Isidro · San Borja · Surquillo · Miraflores

### Zona 3
Lurigancho · Ate · Santa Anita · Lurín · Pachacamac · La Molina · Surco · Barranco · Chorrillos · SJM · VES · VMT

### Zonas propias (no Z1/Z2/Z3)
- **La Victoria** → `LIM_VICTORIA`
- **Lima Cercado** → `LIM_CERCADO`

### Solo manual
- **Gamarra** → `LIM_ECONOMICA`
- **Distribuidores** → `LIM_DISTRIBUIDORES`

---

## 14 distritos pendientes Supervisión

Ancón · Chaclacayo · Chosica · Cieneguilla · El Agustino · Pucusana · Punta Hermosa · Punta Negra · Rímac · San Bartolo · San Luis · San Miguel · Santa María del Mar · Santa Rosa · **Norte Chico**

---

## Clientes por vendedor

Mapas de zonas → zona Victoria/Cercado → **Venta por vendedor** + **Ver clientes**

---

## SQL (orden)

1. `zonas-comerciales-distribuidores.sql`
2. `zonas-comerciales-victoria.sql`
3. `zonas-comerciales-cercado.sql`
4. `zonas-comerciales-lima-zona-1-2-3.sql`
5. `zonas-comerciales-migrar-zona-1-2-3.sql`
6. `zonas-comerciales-victoria-cercado-ubigeo.sql`
7. **`zonas-comerciales-desactivar-zonas-viejas.sql`**

~~`manuales-quitar-ubigeo.sql`~~ — deprecado; no usar.
