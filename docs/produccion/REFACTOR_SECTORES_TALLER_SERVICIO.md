# Refactor: sectores (`sectorjf`) — taller vs servicio

Documento vivo. Objetivo: **mismo flujo de trabajo**, decisiones por `sectorjf.tipo`, sin romper `VC` histórico.

---

## Regla de negocio

| `tipo` | Etiqueta | Significado |
|--------|----------|-------------|
| `0` | TALLER | Interno → stock taller (`articulojf`), **sí tickets** |
| `≠0` | SERVICIO | Externo → servicios/cierres, **no tickets** |

- Un envío de corte = **un solo taller**.
- Todos los sectores del maestro pueden entrar a corte / ingresos (incl. multi); no hay exclusiones extra.
- T5 = interno (ya corregido en el módulo).

---

## Decisiones cerradas (respuestas)

| # | Decisión |
|---|----------|
| 1 | Ideal: guardar **código real** del taller. Sin romper lo de `VC`. Si al implementar se ve muy peligroso → seguir con `VC` en escritura y solo filtrar listas por tipo. |
| 2 | Depende de 1. Estrategia segura abajo: **doble lectura**, escritura nueva con código real cuando el riesgo esté controlado. |
| 3 | Históricos `VC`: **no migrar a ciegas**. Ver estrategia `VC`. |
| 4 | Checkbox “Imprimir tickets” **se elimina**. Imprimir = `tipo == 0` del taller elegido. |
| 5 | Solo se puede enviar a **1** taller por operación. |
| 6 | **T5 es interno** (actualizado en maestro). |
| 7 | No hay sectores a ocultar; recordar **ingresos multi**. |
| 8 | Plan de desarrollo con checklist de pruebas (este doc). Orden: helper → maestro → corte → ingresos/multi → resto → limpieza `VC`. |

---

## Estrategia `VC` (lo que más preocupa)

**No borrar ni reescribir históricos de golpe.**

| Capa | Qué hacer |
|------|-----------|
| **Lectura** | Todo filtro/`if` que hoy asume interno por `taller = 'VC'` pasa a: `taller = 'VC'` **OR** `sectorjf.tipo = 0` (join o helper). Así lo viejo y lo nuevo conviven. |
| **Escritura nueva (meta)** | Al mandar a taller interno, guardar `cod_sector` real (T1, T3, T5…). |
| **Escritura fallback** | Si en una fase el riesgo es alto, seguir escribiendo `VC` solo en ese flujo y documentarlo; no mezclar reglas a medias. |
| **UI listados** | `VC` → etiqueta “VASCO (legado)”; códigos reales → nombre de `sectorjf`. |
| **Migración** | Opcional y **después**: solo si se define un taller default para legado, o se deja `VC` para siempre como “interno sin detalle”. No es requisito para avanzar. |

Helper único (cuando se implemente), p.ej. en `modelos/sectores.modelo.php` / controlador:

- `esInterno($cod)` → `true` si `$cod === 'VC'` **o** `tipo == 0`
- `sectoresPorTipo($tipo)` → lista para selects
- `debeImprimirTickets($cod)` → `esInterno($cod)` (sin checkbox)

---

## Cómo está hoy (problema)

**Corte → taller:** checkbox tickets; cab interna `VC`; select externos sin filtrar; hardcode `T1` para tickets.

**Ingresos / multi:** whitelist `T0…T13`; internos `T1,T3,T5` (multi a veces sin T5); stock `articulojf` vs cierres.

**`sectorjf.tipo`:** casi solo en el maestro.

---

## Plan de desarrollo + checklist de pruebas

Cada fase: implementar → checklist → solo entonces la siguiente. Si algo falla, no avanzar.

### Fase 0 — Base (sin cambiar pantallas)

**Dev**

- [x] Helper `ctrEsInterno` / `ctrSectoresPorTipo` / `ctrDebeImprimirTickets` (incluye `VC` como interno legado) en `modelos/sectores.modelo.php` + `controladores/sectores.controlador.php`
- [x] Lectura de prueba: grilla maestro usa `ctrEsInterno`; probe JSON en `ajax/sectores.ajax.php` (`probeSectores=1`)

**Pruebas** (avisar al usuario)

- [ ] `esInterno('VC')` → true
- [ ] `esInterno('T5')` → true (tras tipo en BD)
- [ ] `esInterno` de un servicio externo conocido → false
- [ ] `sectoresPorTipo(0)` incluye T1/T3/T5 y no incluye un externo típico
- [ ] Nada de producción cambió de comportamiento aún (corte/ingresos iguales)
- [ ] Grilla Administrar sectores sigue mostrando TALLER/SERVICIO correctos

---

### Fase A — Maestro

**Dev**

- [x] Editar tipo en Administrar sectores
- [x] Crear sector con tipo
- [x] Auditar BD vía módulo (2026-08-05): internos = **T0, T1, T3, T5, TD**; resto SERVICIO

**Pruebas**

- [x] Listado maestro: TALLER/SERVICIO coherente (T0 incluido como TALLER)
- [ ] Editar un sector, cambiar tipo, recargar grilla → coincide (si aún no se revalidó)
- [ ] Alta con tipo persiste (Taller o Servicio) y se ve en la grilla

---

### Fase B — Corte → taller

**Dev**

- [x] Quitar checkbox tickets (unitario + global)
- [x] Select de taller obligatorio; un solo taller por envío
- [x] Optgroups: internos / externos (según `ctrSectoresPorTipo`)
- [x] Tickets solo si `ctrDebeImprimirTickets(taller)`
- [x] Cabecera escribe **`cod_sector` real** (ya no fuerza `VC`)
- [x] Lecturas históricas `VC` → siguen como “VASCO” en listados (sin migración)

**Pruebas B.1–B.3**

- [x] OK según usuario (2026-08-05): flujo corte → taller funciona con tipo / código real

**Rollback:** si algo grave → volver a escribir `VC` solo en internos y mantener select/tickets por tipo.

---

### Fase C — Ingresos (+ multi)

**Dev**

- [x] Hardcodes `T1,T3,T5` → `ctrEsInterno` (ingresos, anular, segunda multi, editar-segunda)
- [x] Whitelist `T0…T13` → `ctrSectoresPorTipo` / optgroups (ingresos, segunda, arreglos, en-talleres)
- [x] Multi + ajax: externos = `ctrCodigosPorTipo(1)`; cierres ya no incluyen T0/TD (ahora internos)
- [x] Representantes multi desde primer interno/externo del maestro

**Pruebas C.1–C.3**

- [x] OK según usuario (2026-08-05): avanzar a Fase D

---

### Fase D — Resto producción

**Dev**

- [x] Pagos / eficiencia: select con todos los internos (`ctrSectoresPorTipo(0)`)
- [x] Eficiencia global: botones dinámicos por internos (no solo T1/T3)
- [x] `mdlMostrarArticulosTaller`: rama cierres vs articulojf por `esInterno`
- [x] `buscarEnTaller`: `VC` **o** cabeceras con `tipo=0` (crítico post Fase B)
- [x] Script `procesar_taller.php`: sigue escribiendo `VC` a propósito (legado documentado)

**Pruebas** — *probar ahora*

- [ ] Pagos y eficiencia: aparecen T0, T1, T3, T5, TD
- [ ] Eficiencia global: filtrar por T5 (u otro interno nuevo)
- [ ] Ingreso desde taller interno con cab recién enviada (código real, no solo VC) descuenta saldo `entaller_cabjf`
- [ ] Ingreso externo (T4…) sigue por cierres
- [ ] Cabeceras viejas `VC` siguen consumiéndose al ingresar

---

### Fase E — Limpieza `VC` (opcional, aplazada)

**Estado:** dejada en espera (2026-08-05). El sistema queda operativo con compat `VC` + códigos reales.

**Dev (cuando se retome)**

- [ ] Inventario: ¿quién aún escribe o filtra solo `VC`?
- [ ] Decidir: dejar `VC` eterno como legado **o** migración controlada a un taller default
- [ ] Quitar ramas muertas solo cuando el checklist D esté verde

**Pruebas**

- [ ] Reportes con rango de fechas que incluya solo `VC` y solo códigos reales
- [ ] Ningún insert nuevo fuerza `VC` salvo decisión explícita

---

## Orden de trabajo recomendado

```
Fase 0 (helper) → A (auditar tipos) → B (corte) → C (ingresos/multi) → D → E
```

No saltar a C si B.3 (VC) no está estable.

---

## Inventario hardcodes (prioridad)

| Zona | Hardcode | Riesgo |
|------|----------|--------|
| Corte | `VC`, checkbox, `=='T1'` | Stock / tickets / servicio |
| Ingresos | `T1,T3,T5` | Stock |
| Multi | internos desalineados | Artículos invisibles |
| Listas UI | `T0…T13` | Talleres nuevos invisibles |
| Scripts | `TALLER_INTERNO='VC'` | Batch |
| Pagos/efic. | solo T1/T3 | Filtros incompletos |

---

## Bitácora

| Fecha | Qué se dijo / decidió |
|-------|------------------------|
| 2026-08-05 | Documentar; tipo Taller=interno / Servicio=externo; editar tipo en maestro. |
| 2026-08-05 | Corte: filtrar internos de lista externa; cab real vs VC; tickets por tipo; plan + dudas. |
| 2026-08-05 | Respuestas: código real sin romper VC; históricos no migrar a ciegas; quitar checkbox; 1 taller/envío; T5 interno; sin exclusiones (hay multi); plan con checklist de pruebas. |
| 2026-08-05 | Fase 0 implementada: helpers + probe ajax + grilla maestro vía `ctrEsInterno`. Pendiente checklist de pruebas del usuario. |
| 2026-08-05 | Fase A: alta de sector con tipo. Siguiente tras pruebas: Fase B (corte → taller). |
| 2026-08-05 | Auditoría maestro: T0 también es TALLER (junto a T1, T3, T5, TD). Lista OK. |
| 2026-08-05 | Fase B implementada: sin checkbox; select con optgroups; tickets/stock por `tipo`; cab con código real. Pendiente checklist B.1–B.3. |
| 2026-08-05 | Fase B OK usuario. Fase C implementada (ingresos/multi/segundas/arreglos/en-talleres por tipo). Pendiente pruebas C. |
| 2026-08-05 | Fase C OK. Fase D: pagos/efic., art. taller por tipo, buscarEnTaller VC+internos, EG dinámico. Pendiente pruebas D. |
| 2026-08-05 | Fase E aplazada a propósito. Refactor cerrado en D con compat VC. |
