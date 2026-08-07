# Plan: Helpdesk interno (TI)

## Para qué

Registrar pedidos de soporte y desarrollo que hoy viven en WhatsApp, correos y llamadas, para poder medir volumen, tiempos y backlog.

## Qué tenemos

| Pieza | Descripción |
|-------|-------------|
| **Tickets** | Asunto, descripción, pasos, área, contacto, adjuntos |
| **Quién** | Solicitante + asignado (TI / soporte) |
| **Tipo** | Incidencia · Requerimiento · Consulta · Otro · Desarrollo · Corrección |
| **Prioridad** | Baja · Media · Alta |
| **Estados** | Abierto → En progreso → Esperando usuario → Cerrado |
| **Historial** | Comentarios y cambios de estado con fecha |
| **Adjuntos** | JPG/PNG/PDF/DOC/XLS, máx 10 MB, hasta 5 por ticket |
| **Catálogo** | Áreas y módulos (agrupados por sección del menú) en `controladores/helpdesk-catalogo.json` |
| **UI** | Tabs Nuevo ticket / Mis tickets (o Bandeja si `gestionar`) |
| **Permisos** | Sector `ti` → `helpdesk`: `ver` / `registrar` / `gestionar` |
| **Indicadores** | Pendiente (Fase 2) |

Ruta: `helpdesk` · Menú: **TI → Helpdesk**

Fuera de alcance actual: rich-text, notificaciones, portal aparte, WhatsApp automático, SLAs complejos.

## Quién hace qué

| Rol | Permiso | Puede |
|-----|---------|--------|
| Usuario | `ver` + `registrar` | Crear ticket, comentar, ver los suyos |
| Soporte / TI | + `gestionar` | Ver todos (Bandeja), asignar, cambiar estado/prioridad |
| Registro manual | `registrar` (+ `gestionar` si asigna) | Abrir ticket por pedidos de WA/correo/llamada |

## SQL

1. Base: [`docs/sql/helpdesk.sql`](../sql/helpdesk.sql) (ya ejecutado)
2. Mockup / adjuntos: [`docs/sql/helpdesk-alter-mockup.sql`](../sql/helpdesk-alter-mockup.sql) — **ejecutar ahora**

Tablas: `helpdesk_ticketjf`, `helpdesk_comentariojf`, `helpdesk_adjuntojf`  
Archivos: `vistas/img/helpdesk/`

## Archivos

| Rol | Path |
|-----|------|
| Vista | `vistas/modulos/ti/helpdesk.php` |
| CSS / JS | `vistas/css/helpdesk.css`, `vistas/js/helpdesk.js` |
| AJAX | `ajax/helpdesk.ajax.php` |
| Controlador / modelo | `controladores/helpdesk.controlador.php`, `modelos/helpdesk.modelo.php` |
| Permisos | `controladores/permisos-modulos.json` → `ti.helpdesk` |

## Orden de trabajo

### Fase 0 — Acuerdos

- [x] Tipos y estados (mixto mockup + TI)
- [x] Módulo dentro de vascorp
- [ ] Confirmar ID de soporte (además del `6`)
- [ ] Regla de uso: pedido por WA/correo → alguien lo registra sí o sí

### Fase 1 — Base + mockup

- [x] Tablas base + ALTER mockup
- [x] Módulo + permiso + menú + ruta
- [x] Formulario tipo mockup (cards, área, pasos, contacto, tips)
- [x] Lista / bandeja + detalle + comentarios + asignación
- [x] Adjuntos al crear + descarga
- [ ] Ejecutar `helpdesk-alter-mockup.sql` y probar

### Fase 2 — Indicadores

- [ ] Dashboard / reporte del período
- [ ] Export Excel (si lo piden gerencia)

### Fase 3 — Después

- [ ] Notificación por correo al cambiar estado
- [ ] Catálogo de módulos/sistemas
- [ ] Entrada desde correo/WhatsApp (semi-auto)
- [ ] Editor enriquecido

## Criterio de éxito

Si en 2–4 semanas casi todo pedido queda en ticket (aunque lo registres tú), los indicadores salen solos. Si WhatsApp sigue siendo la única fuente de verdad, el helpdesk no sirve.
